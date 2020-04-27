<?php namespace Andesite\GhostGenerator;

use Andesite\Core\ServiceManager\Service;
use Andesite\DBAccess\ConnectionFactory;
use Andesite\Ghost\Field;
use Andesite\Ghost\GhostManager;
use Andesite\Ghost\Model;
use Andesite\Ghost\Relation;
use Andesite\Util\CodeFinder\CodeFinder;
use Application\Ghosts;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class GhostGenerator{

	use Service;

	protected $ghosts;
	protected $ghostNamespace;
	protected $ghostPath;
	/** @var SymfonyStyle */
	protected $style;

	public function __invoke(SymfonyStyle $style = null){
		$this->ghosts = GhostManager::Module()->getGhosts();
		$this->style = is_null($style) ? new SymfonyStyle(new ArgvInput(), new DummyOutput()) : $style;
		$this->ghostNamespace = GhostManager::Module()->getNamespace();
		$this->ghostPath = realpath(CodeFinder::Service()->Psr4ResolveNamespace($this->ghostNamespace));
		$this->style->title('GHOST CREATOR');

		foreach ($this->ghosts as $name => $ghost){
			$database = $ghost['database'];
			$table = $ghost['table'];
			$this->style->section($name);
			$exists = $this->generateEntity($name, $table);
			$this->generateGhostFromDatabase($name, $table, $database);
			if ($exists) $this->updateGhost($name);

		}
		$this->style->success('done.');
	}

	protected function updateGhost($name){

		$file = "{$this->ghostPath}/{$name}.ghost.php";
		$this->style->write("Update Ghost ");
		$ghostClass = $this->ghostNamespace . '\\' . $name;

		/** @var Model $model */
		$model = $ghostClass::$model;

		$annotations = [];
		$properties = [];
		$getterSetter = [];
		$attachmentConstants = [];

		foreach ($model->fields as $field){

			$type = '';
			switch ($field->type){
				case Field::TYPE_BOOL:
					$type = 'boolean';
					break;
				case Field::TYPE_DATE:
					$type = '\Valentine\Date';
					break;
				case Field::TYPE_DATETIME:
					$type = '\DateTime';
					break;
				case Field::TYPE_ENUM:
				case Field::TYPE_STRING:
					$type = 'string';
					break;
				case Field::TYPE_SET:
					$type = 'array';
					break;
				case Field::TYPE_INT:
				case Field::TYPE_ID:
					$type = 'int';
					break;
				case Field::TYPE_FLOAT:
					$type = 'float';
					break;
			}
			$properties[] = "\t" . "/** @var {$type} {$field->name} */";
			$properties[] = "\t" . ( $field->protected ? 'protected' : 'public' ) . " \${$field->name};";

			if ($field->protected){
				if ($field->setter !== false && $field->getter !== false)
					$annotations[] = " * @property $" . $field->name;
				elseif ($field->getter !== false)
					$annotations[] = " * @property-read $" . $field->name;
				elseif ($field->setter !== false)
					$annotations[] = " * @property-write $" . $field->name;

				if (is_string($field->getter))
					$getterSetter[] = "\t" . 'abstract protected function ' . $field->getter . '();';

				if (is_string($field->setter))
					$getterSetter[] = "\t" . 'abstract protected function ' . $field->setter . '($value);';
			}
		}

		foreach ($model->virtuals as $field){

			if (strpos($field['type'], '\\') !== false){
				$field['type'] = '\\' . trim($field['type'], '\\');
			}

			if ($field['setter'] !== false && $field['getter'] !== false)
				$annotations[] = " * @property " . $field['type'] . " $" . $field['name'];
			elseif ($field['getter'] !== false)
				$annotations[] = " * @property-read " . $field['type'] . " $" . $field['name'];
			elseif ($field['setter'] !== false)
				$annotations[] = " * @property-write " . $field['type'] . " $" . $field['name'];
			if (is_string($field['getter']))
				$getterSetter[] = "\t" . 'abstract protected function ' . $field['getter'] . '()' . ( $field['type'] ? ':' . $field['type'] : '' ) . ';';
			if (is_string($field['setter']))
				$getterSetter[] = "\t" . 'abstract protected function ' . $field['setter'] . '(' . $field['type'] . '$value);';
		}

		foreach ($model->getAttachmentStorage()->getCategories() as $category){
			$annotations[] = ' * @property-read AttachmentCategoryManager $' . $category->getName();
			$attachmentConstants[] = "\tconst A_" . $category->getName() . ' = "' . $category->getName() . '";';

		}

		foreach ($model->relations as $relation){
			switch ($relation->type){
				case Relation::TYPE_BELONGSTO:
					$annotations[] = ' * @property-read \\' . $relation->descriptor['ghost'] . ' $' . $relation->name;
					break;
				case Relation::TYPE_HASMANY:
					$annotations[] = ' * @property-read \\' . $relation->descriptor['ghost'] . '[] $' . $relation->name;
					$annotations[] = ' * @method \\' . $relation->descriptor['ghost'] . '[] ' . $relation->name . '($order = null, $limit = null, $offset = null)';
					break;
			}
		}

		$template = file_get_contents($file);
		$template = str_replace('/*ghost-generator-properties*/', join("\n", $properties), $template);
		$template = str_replace(' * ghost-generator-annotations', join("\n", $annotations), $template);
		$template = str_replace('/*ghost-generator-getters-setters*/', join("\n", $getterSetter), $template);
		$template = str_replace('/*attachment-constants*/', join("\n", $attachmentConstants), $template);

		$this->style->write("- {$file}");
		file_put_contents($file, $template);
		$this->style->writeln(" - [OK]");
	}

	protected function generateGhostFromDatabase($name, $table, $database){

		$file = "{$this->ghostPath}/{$name}.ghost.php";

		$this->style->write("Connecting to database ");
		$this->style->write("- ${database}");
		/** @var \Andesite\DBAccess\Connection\PDOConnection $connection */
		$connection = ConnectionFactory::Module()->get($database);

		$smartAccess = $connection->createSmartAccess();
		$this->style->writeln(" - [OK]");

		$this->style->write("Fetching table information ");
		$this->style->write("- ${table}");
		//$fields = $smartAccess->getFieldData($table);
		$schemafields = $smartAccess->getTableSchema($table);
		/** @var \Andesite\GhostGenerator\DBFieldConverter[] $fields */
		$fields = [];

		foreach ($schemafields as $field){
			$fields[$field['COLUMN_NAME']] = new DBFieldConverter($field);
		}
		$this->style->writeln(" - [OK]");

		$constants = [];
		$addFields = [];
		$comparers = [];
		$fieldConstants = [];
		$validators = [];
		$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
		foreach ($fields as $field){

			$options = null;
			if (is_array($field->options)){
				foreach ($field->options as $value){
					$constants[] = "\t" . 'const V_' . $field->name . '_' . $value . ' = "' . $value . '";';
				}
			}
			$comparers[] = "\t\t" . "public static function f_" . $field->name . "(){return new Comparison('" . $field->name . "');}";
			$addFields[] = "\t\t" . '$model->addField("' . $field->name . '", Field::TYPE_' . $field->fieldType . ',' . $encoder->encode($field->options, ['whitespace' => false]) . ');';
			$fieldConstants[] = "\t" . 'const F_' . $field->name . ' = "' . $field->name . '";';
			foreach ($field->validators as $validator){
				$validators[] = "\t\t" . '$model->addValidator("' . $field->name . '", new \\' . $validator[0] . '(' . ( count($validator) > 1 ? $encoder->encode($validator[1], ['whitespace' => false]) : '' ) . '));';
			}

		}
		$addFields[] = "\t\t" . '$model->protectField("id");';

		$template = file_get_contents(__DIR__ . '/@resource/ghost.txt');

		$template = str_replace('{{name}}', $name, $template);
		$template = str_replace('{{table}}', $table, $template);
		$template = str_replace('{{connectionName}}', $database, $template);
		$template = str_replace('{{namespace}}', $this->ghostNamespace, $template);
		$template = str_replace('{{add-fields}}', join("\n", $addFields), $template);
		$template = str_replace('{{validators}}', join("\n", $validators), $template);
		$template = str_replace('{{constants}}', join("\n", $constants), $template);
		$template = str_replace('{{fieldConstants}}', join("\n", $fieldConstants), $template);
		$template = str_replace('{{comparers}}', join("\n", $comparers), $template);

		$this->style->write("Generate Ghost ");
		$this->style->write("- {$file}");
		file_put_contents($file, $template);
		$this->style->writeln(" - [OK]");
	}

	protected function generateEntity($name, $table){
		$this->style->write("Generate Entity ");
		$file = "{$this->ghostPath}/{$name}.php";
		$this->style->write("- {$file}");

		if (file_exists($file)){
			$this->style->writeln(" - [ALREADY EXISTS]");
			return true;
		}else{
			$template = file_get_contents(__DIR__ . '/@resource/entity.txt');
			$template = str_replace('{{namespace}}', $this->ghostNamespace, $template);
			$template = str_replace('{{name}}', $name, $template);
			$template = str_replace('{{table}}', $table, $template);
			file_put_contents($file, $template);
			$this->style->writeln(" - [OK]");
			return false;
		}
	}
}
