<?php namespace Andesite\Core\Constant;

use Andesite\Core\ServiceManager\ServiceContainer;
use CaseHelper\CaseHelperFactory;
use Minime\Annotations\Reader;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Andesite\Core\Env\Env;

class Constant{

	public static function generate(){
		/** @var Reader $reader */
		$reader = ServiceContainer::get(Reader::class);
		$reflection = new \ReflectionClass(static::class);
		$annotations = $reader->getAnnotations($reflection);

		$constants = $annotations->getAsArray('const');
		$consts = [];
		foreach ($constants as $constant){
			$key = static::key($constant);
			$value = static::value($constant, $annotations->get('prefix'));
			$consts[$key] = $value;
		}

		static::generatePhp($consts, $reflection->getFileName());
		static::generateJs($consts, $reflection->getShortName(), $annotations->getAsArray('js'));
	}

	protected static function key($string){ return CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_SNAKE_CASE)->toPascalCase(strtr($string, ' -', '__')); }

	protected static function value($string, $prefix){
		if($prefix === null) $prefix = str_replace("\\", ".", get_called_class());
		return ($prefix ? $prefix . '.' : '') . static::key($string);
	}

	private static function generateJs($consts, $defaultAs, $jss){
		$jss = array_map(function ($js) use ($defaultAs){ return $js . (strpos($js, ' as ') === false ? ' as ' . $defaultAs : ''); }, $jss);
		foreach ($jss as $js){
			[$file, $as] = explode(' as ', $js);
			file_put_contents(Env::Service()->get('root') . $file, "let " . $as . " = " . json_encode($consts, JSON_PRETTY_PRINT) . ";\nexport default " . $as . ";\n");
		}
	}

	private static function generatePhp($consts, $file){

		$ast = (new ParserFactory())->create(ParserFactory::PREFER_PHP7)->parse(file_get_contents($file));

		// remove all constants
		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class extends NodeVisitorAbstract{
			public function leaveNode(Node $node){ if ($node instanceof \PhpParser\Node\Stmt\ClassConst) return NodeTraverser::REMOVE_NODE; }
		});
		$ast = $traverser->traverse($ast);

		// insert new constants
		$traverser = new NodeTraverser();
		$traverser->addVisitor(new class($consts) extends NodeVisitorAbstract{
			protected $consts;
			public function __construct($consts){ $this->consts = $consts; }
			public function enterNode(Node $node){ if ($node instanceof \PhpParser\Node\Stmt\Class_) foreach ($this->consts as $key => $value) $node->stmts[] = new \PhpParser\Node\Stmt\ClassConst([new \PhpParser\Node\Const_($key, new Node\Scalar\String_($value))]); }
		});
		$ast = $traverser->traverse($ast);

		$prettyPrinter = new Standard();
		file_put_contents($file, '<?php ' . $prettyPrinter->prettyPrint($ast));
	}

}