<?php namespace Andesite\Ghost;

use Andesite\Attachment\AttachmentRepository;
use Andesite\Attachment\ThumbnailResponder;
use Andesite\Core\Module\Module;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\ConnectionFactory;
use Andesite\Mission\Web\Routing\Router;
use Andesite\Util\CodeFinder\CodeFinder;
use Composer\Autoload\ClassLoader;
use Minime\Annotations\Reader;

class GhostManager extends Module{

	/** @var \Minime\Annotations\Reader */
	private $reader;
	private $ghosts = [];
	private $decorator;
	private $namespace;
	private $attachment = null;
	/** @var \Composer\Autoload\ClassLoader */
	private $classLoader;

	public function getGhosts(){ return $this->ghosts; }
	public function getAttachment(){ return $this->attachment; }
	public function getNamespace(){ return $this->namespace; }

	public function __construct(Reader $reader, ClassLoader $classLoader){
		$this->reader = $reader;
		$this->classLoader = $classLoader;
	}

	public function routeThumbnail(Router $router){
		AttachmentRepository::Module()->routeThumbnails($router, $this->attachment);
	}

	public function setup($config){
		$this->namespace = $config['namespace'];
		$this->decorator = $config['decorator'];
		$this->attachment = $config['attachment'];
	}

	protected function load(ConnectionFactory $factory, AttachmentRepository $attachmentRepository){
		$ghosts = $this->reader->getClassAnnotations($this->decorator)->getAsArray('ghost');
		foreach ($ghosts as $ghost){
			$ghost = explode(':', str_replace('@', ':', $ghost));
			if (count($ghost) === 1) $ghost[1] = lcfirst($ghost[0]);
			if (count($ghost) === 2) $ghost[2] = 'default';
			$this->ghosts[$ghost[0]] = [
				'name'     => $ghost[0],
				'table'    => $ghost[1],
				'database' => $ghost[2],
				'class'    => $this->namespace . '\\' . $ghost[0],
			];
		}
		$this->init();
	}

	public function init(){
		$decoratorObject = new $this->decorator();
		$location = CodeFinder::Service()->Psr4ResolveNamespace($this->namespace);

		foreach ($this->ghosts as $ghost){
			$this->classLoader->addClassMap([$ghost['class'] . 'Ghost' => realpath($location . $ghost['name'] . '.ghost.php')]);
		}

		foreach ($this->ghosts as $ghost){
			$decoratorMethod = lcfirst($ghost['name']);
			if (file_exists(realpath($location . $ghost['name'] . '.ghost.php')) && class_exists($ghost['class'])){
				$model = $ghost['class']::init($this);
				if (method_exists($decoratorObject, $decoratorMethod)) $decoratorObject->$decoratorMethod($model);
			}
		}
	}
}