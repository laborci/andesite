<?php namespace Andesite\GhostGenerator;

use Andesite\Attachment\Collection;
use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\Filter\Comparison;
use Andesite\DBAccess\Connection\PDOConnection;
use Andesite\DBAccess\ConnectionFactory;
use Andesite\Ghost\Field;
use Andesite\Ghost\Ghost;
use Andesite\Ghost\GhostManager;
use Andesite\Ghost\Model;
use Andesite\Ghost\Relation;
use Andesite\Util\CodeFinder\CodeFinder;
use Application\Ghost\Article;
use Application\Ghost\Article2;
use Application\Ghosts;
use CaseHelper\CamelCaseHelper;
use CaseHelper\CaseHelperFactory;
use CaseHelper\PascalCaseHelper;
use CaseHelper\SnakeCaseHelper;
use CaseHelper\Test\Unit\CaseHelper\SpaceCaseInputTest;
use Minime\Annotations\Reader;
use ReflectionClass;
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
	protected ?SymfonyStyle $style;

	public function setup(SymfonyStyle $style = null): GhostGenerator{
		$this->style = is_null($style) ? new SymfonyStyle(new ArgvInput(), new DummyOutput()) : $style;
		return $this;
	}

	public function create($name){

		$namespaces = GhostManager::Module()->getNamespace();

		$ghostPath = realpath(CodeFinder::Service()->Psr4ResolveNamespace($namespaces['ghost']));
		$shadowPath = realpath(CodeFinder::Service()->Psr4ResolveNamespace($namespaces['shadow']));
		$finderPath = realpath(CodeFinder::Service()->Psr4ResolveNamespace($namespaces['finder']));

		$table = ( new CamelCaseHelper() )->toSnakeCase($name);
		$class = ucfirst(( new SnakeCaseHelper() )->toCamelCase($table));

		$translate = [
			"{{name}}"             => $class,
			"{{table}}"            => $table,
			"{{ghost-namespace}}"  => $namespaces['ghost'],
			"{{shadow-namespace}}" => $namespaces['shadow'],
			"{{finder-namespace}}" => $namespaces['finder'],
		];

		$this->style->writeln("Generate Entity ");

		$files = [
			"ghost.txt"  => "{$ghostPath}/{$name}.php",
			"shadow.txt" => "{$shadowPath}/__{$name}.php",
			"finder.txt" => "{$finderPath}/__{$name}.php",
		];

		foreach ($files as $templateFile => $file){
			$this->style->write("- {$file}");
			if (file_exists($file)){
				$this->style->writeln(" - [ALREADY EXISTS]");
			}else{
				$template = file_get_contents(__DIR__ . '/@resource/' . $templateFile);
				$template = strtr($template, $translate);
				file_put_contents($file, $template);
				$this->style->writeln(" - [OK]");
			}
		}

	}

	public function generate($name = null){

		$namespaces = GhostManager::Module()->getNamespace();

		$ghosts = is_null($name) ? GhostManager::Module()->getGhosts() : [$namespaces['ghost'] . '\\' . $name];

		foreach ($ghosts as $ghost){

			$ref = new ReflectionClass($ghost);

			/** @var Model $model */
			$model = $ghost::$model;

			$name = $ref->getShortName();
			$this->style->writeln($name);

			$model = new Model($ghost, $model->connection->getName(), $model->table, $model->storage, $model->mutable);
			$fieldsdefinitions = $this->fetch($model);
			$this->updateShadow($model, $fieldsdefinitions);

			if (count($model->attachmentStorage->categories)) $model->attachmentStorage->initialize();
		}
		$this->style->success('done.');
	}

	protected function fetch(Model $model){
		$smartAccess = $model->connection->createSmartAccess();
		$this->style->writeln("- Fetching information table $model->table");
		$schemafields = $smartAccess->getTableSchema($model->table);
		/** @var \Andesite\GhostGenerator\DBFieldConverter[] $fields */
		$fields = [];
		$fieldRef = new ReflectionClass(Field::class);

		foreach ($schemafields as $field){
			$f = new DBFieldConverter($field);
			$model->addField($f->name, $fieldRef->getConstant('TYPE_' . $f->fieldType), $f->options);
			$fieldsdefinition = [
				'name'       => $f->name,
				'type'       => $f->fieldType,
				'options'    => $f->options,
				'validators' => [],
				'readonly'   => $f->virtual,
			];
			foreach ($f->validators as $validator){
				$fieldsdefinition['validators'][] = [
					'name'    => $f->name,
					'type'    => $validator[0],
					'options' => count($validator) > 1 ? $validator[1] : null,
				];
			}
			$fieldsdefinitions[] = $fieldsdefinition;
		}
		$model->protectField('id');

		$ghost = $model->ghost;
		$ref = new ReflectionClass($ghost);
		$extendModel = $ref->getMethod('extendModel');
		$extendModel->setAccessible(true);
		$extendModel->invoke(null, $model);
		return $fieldsdefinitions;
	}

	protected function updateShadow(Model $model, $fielddefinition){
		$name = ( new ReflectionClass($model->ghost) )->getShortName();

		$this->style->writeln("- Updating $name shadow");

		$encoder = new \Riimu\Kit\PHPEncoder\PHPEncoder();
		$types = [
			Field::TYPE_JSON     => 'array',
			Field::TYPE_BOOL     => 'bool',
			Field::TYPE_DATE     => '\Valentine\Date',
			Field::TYPE_DATETIME => '\DateTime',
			Field::TYPE_TIME     => '\DateTime',
			Field::TYPE_ENUM     => 'string',
			Field::TYPE_SET      => 'array',
			Field::TYPE_STRING   => 'string',
			Field::TYPE_GUID     => 'string',
			Field::TYPE_INT      => 'int',
			Field::TYPE_ID       => 'int',
			Field::TYPE_FLOAT    => 'float',
		];
		$protectFields = [];
		$fieldAdditions = [];
		$fieldValidators = [];
		$fields = [];
		$fieldConstants = [];
		$enumConstants = [];
		$comparators = [];
		$collections = [];
		$protecteds = [];
		$virtuals = [];
		$abstracts = [];
		$relations = [];
		$collectionNames = [];
		$ghostDescriptor = [
			'fields' => [],
			'attachments'=>[],
			'relations'=>[],
		];

		foreach ($fielddefinition as $field){
			$fieldAdditions[] = "\t\t\t" . '->addField("' . $field['name'] . '", Field::TYPE_' . $field['type'] . ', ' . $encoder->encode($field['options'], ['whitespace' => false]) . ')';
			if ($field['type'] === Field::TYPE_GUID) $protectFields [] = "\t\t\t->guid('" . $field['name'] . "')";
			if ($field['readonly']) $protectFields[] = "\t\t\t->readonly('" . $field['name'] . "', ".$field['readonly'].")";
			$ghostDescriptor['fields'][$field['name']] = [
				'name'=>$field['name'],
				'type'=>$field['type'],
				'readonly'=>$field['readonly'] ? $field['readonly'] : false,
				'options'=>$field['options']
			];
		}
		foreach ($fielddefinition as $field){
			if(!$field['readonly']){
				foreach ($field['validators'] as $validator){
					$fieldValidators[] = "\t\t\t" . '->addValidator("' . $field['name'] . '", new \\' . $validator['type'] . '(' . ( is_null($validator['options']) ? '' : $encoder->encode($validator['options'], ['whitespace' => false]) ) . '))';
				}
			}
		}
		foreach ($model->fields as $field){

			$fields[] = "\t" . ( $field->protected ? 'protected' : 'public' ) . " ?" . $types[$field->type] . " \$" . $field->name . " = null;";

			$fieldConstants[] = "\t" . 'const ' . $field->name . ' = "' . $field->name . '";';

			$comparators[] = ' * @method static Comparison ' . $field->name . '($isin = null)';

			if (is_array($field->options)){
				foreach ($field->options as $option){
					$enumConstants[] = "\t" . 'const ' . $field->name . '__' . $option . ' = "' . $option . '";';
				}
			}

			if ($field->protected){
				if ($field->setter !== false && $field->getter !== false) $protecteds[] = " * @property $" . $field->name;
				elseif ($field->getter !== false) $protecteds[] = " * @property-read $" . $field->name;
				elseif ($field->setter !== false) $protecteds[] = " * @property-write $" . $field->name;
				if (is_string($field->getter)) $abstracts[] = "\t" . 'abstract protected function ' . $field->getter . '();';
				if (is_string($field->setter)) $abstracts[] = "\t" . 'abstract protected function ' . $field->setter . '($value);';
			}
		}

		foreach ($model->attachmentStorage->categories as $category){
			$collections[] = " * @property-read Collection $" . $category->name;
			$collectionNames[] =  "\t" . 'const attachment_colection__' . $category->name . ' = "' . $category->name . '";';
			$ghostDescriptor['attachments'][$category->name] = [
				'name'=>$category->name,
				'extensions'=>$category->acceptedExtensions,
				'maxFileCount'=>$category->maxFileCount,
				'maxFileSize'=>$category->maxFileSize,
				'meta'=>$category->metaDefinition ? $category->metaDefinition->getFields() : null
			];
		}

		foreach ($model->virtuals as $field){
			if (strpos($field['type'], '\\') !== false) $field['type'] = '\\' . trim($field['type'], '\\');
			if ($field['type']) $field['type'] .= ' ';
			if ($field['setter'] !== false && $field['getter'] !== false) $virtuals[] = " * @property " . $field['type'] . "$" . $field['name'];
			elseif ($field['getter'] !== false) $virtuals[] = " * @property-read " . $field['type'] . "$" . $field['name'];
			elseif ($field['setter'] !== false) $virtuals[] = " * @property-write " . $field['type'] . "$" . $field['name'];
			if (is_string($field['getter'])) $abstracts[] = "\t" . 'abstract protected function ' . $field['getter'] . '()' . ( $field['type'] ? ':' . $field['type'] : '' ) . ';';
			if (is_string($field['setter'])) $abstracts[] = "\t" . 'abstract protected function ' . $field['setter'] . '(' . $field['type'] . '$value);';
		}

		foreach ($model->relations as $relation){
			$ghostDescriptor['relations'] = [
				'name'=>$relation->name,
				'descriptor'=>$relation->descriptor,
				'type'=>$relation->type
			];
			switch ($relation->type){
				case Relation::TYPE_BELONGSTO:
					$relations[] = ' * @property-read \\' . $relation->descriptor['ghost'] . ' $' . $relation->name;
					break;
				case Relation::TYPE_BELONGSTOMANY:
					$relations[] = ' * @property-read \\' . $relation->descriptor['ghost'] . '[] $' . $relation->name;
					$relations[] = ' * @method \\' . $relation->descriptor['ghost'] . '[] ' . $relation->name . '()';
					break;
				case Relation::TYPE_HASMANY:
					$relations[] = ' * @property-read \\' . $relation->descriptor['ghost'] . '[] $' . $relation->name;
					$relations[] = ' * @method \\' . $relation->descriptor['ghost'] . '[] ' . $relation->name . '($order = null, $limit = null, $offset = null)';
					break;
			}
		}

		$template = file_get_contents(__DIR__ . '/@resource/shadow.txt');
		$template = str_replace('# @virtuals', join("\n", $virtuals), $template);
		$template = str_replace('# @protecteds', join("\n", $protecteds), $template);
		$template = str_replace('# @collections', join("\n", $collections), $template);
		$template = str_replace('# @relations', join("\n", $relations), $template);
		$template = str_replace('# @comparators', join("\n", $comparators), $template);
		$template = str_replace('# abstracts', join("\n", $abstracts), $template);
		$template = str_replace('# enum-constants', join("\n", $enumConstants), $template);
		$template = str_replace('# field-constants', join("\n", $fieldConstants), $template);
		$template = str_replace('# fields', join("\n", $fields), $template);
		$template = str_replace('# field-additions', join("\n", $fieldAdditions), $template);
		$template = str_replace('# field-validators', join("\n", $fieldValidators), $template);
		$template = str_replace('# protect-fields', join("\n", $protectFields), $template);
		$template = str_replace('# collection-names', join("\n", $collectionNames), $template);

		$finderNamesapce = GhostManager::Module()->getNamespace()['finder'];
		$shadowNamespace = GhostManager::Module()->getNamespace()['shadow'];

		$template = str_replace('{{shadow-namespace}}', $shadowNamespace, $template);
		$template = str_replace('{{finder-namespace}}', $finderNamesapce, $template);
		$template = str_replace('{{name}}', $name, $template);

		$shadowPath = realpath(CodeFinder::Service()->Psr4ResolveNamespace($shadowNamespace));

		file_put_contents($shadowPath . '/__' . $name . '.php', $template);
		if(!is_dir($shadowPath . '/../descriptors/')) mkdir($shadowPath . '/../descriptors/', 0777);
		file_put_contents($shadowPath . '/../descriptors/' . (new PascalCaseHelper())->toKebabCase($name) . '.json', json_encode($ghostDescriptor, JSON_PRETTY_PRINT+ JSON_UNESCAPED_SLASHES+ JSON_UNESCAPED_UNICODE));
		file_put_contents($shadowPath . '/../descriptors/' . (new PascalCaseHelper())->toKebabCase($name) . '.js', 'let '.$name.' = '.json_encode($ghostDescriptor, JSON_PRETTY_PRINT+ JSON_UNESCAPED_SLASHES+ JSON_UNESCAPED_UNICODE).";\nexport default ".$name.';');
	}
}
