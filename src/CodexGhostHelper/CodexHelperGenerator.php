<?php namespace Andesite\CodexGhostHelper;

use Andesite\DBAccess\ConnectionFactory;
use Andesite\Ghost\GhostManager;
use CaseHelper\CaseHelperFactory;
use Minime\Annotations\Reader;
use Andesite\DBAccess\Connection\PDOConnection;
use Andesite\Ghost\Relation;
use Andesite\Ghost\Model;
use Andesite\Util\CodeFinder\CodeFinder;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CodexHelperGenerator{

	use Service;

	public function execute(SymfonyStyle $style, array $config){

		$namespace = $config['namespace'];
		$ghosts = GhostManager::Module()->getGhosts();

		foreach ($ghosts as  $ghost) if(class_exists($ghost['class'])){

			$class = $namespace . '\\' . $ghost['name'] . 'Helper';
			$ghostClass = $ghost['class'];
			/** @var Model $model */
			$model = $ghostClass::$model;

			// READ EXISTING ANNOTATIONS as TRANSLATIONS
			$translations = new Translation();
			if (class_exists($class)){
				/** @var \Minime\Annotations\Reader $reader */
				$reader = ServiceContainer::get(Reader::class);
				$translations->addFromAnnotations($reader->getClassAnnotations($class)->getAsArray('label-field'));
				$translations->addFromAnnotations($reader->getClassAnnotations($class)->getAsArray('label-attachment'));
			}

			/** @var Field[] $fields */
			$fields = [];
			foreach ($model->fields as $field) $fields[] = new Field('field', $field->name, $field->options, $translations);
			foreach ($model->getAttachmentStorage()->getCategories() as $category) $fields[] = new Field('attachment', $category->getName(), [], $translations);

			$fieldCollection = [];
			$fieldConstructorCollection = [];
			$annotationCollection = [];
			foreach ($fields as $field){
				$annotationCollection = array_merge($annotationCollection, $field->getTranslateAnnotations());
				$fieldCollection[] = $field->getField();
				$fieldConstructorCollection[] = $field->getFieldConstructor();
			}

			$template = file_get_contents(__DIR__ . '/@resource/helper.txt');

			$template = str_replace('{{name}}', $ghost['name'], $template);
			$template = str_replace('{{namespace}}', $namespace, $template);
			$template = str_replace('{{ghost}}', $ghost['class'], $template);
			$template = str_replace('{{fields}}', join("\n", $fieldCollection), $template);
			$template = str_replace('{{fieldConstructors}}', join("\n", $fieldConstructorCollection), $template);
			$template = str_replace('{{annotations}}', join("\n", $annotationCollection), $template);

			$filename = CodeFinder::Service()->Psr4ResolveClass($namespace . '\\' . $ghost['name'] . 'Helper');
			file_put_contents($filename, $template);

			$style->writeln(realpath($filename).' done.');
		}
	}
}



