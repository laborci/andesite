<?php namespace Andesite\Ghost;

//use Andesite\Attachment\AttachmentRepository;
use Andesite\Attachment\ThumbnailResponder;
use Andesite\Core\Module\Module;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\ConnectionFactory;
use Andesite\Mission\Web\Routing\Router;
use Andesite\Util\CodeFinder\CodeFinder;
use Composer\Autoload\ClassLoader;
use Minime\Annotations\Reader;
use ReflectionClass;


class GhostManager extends Module{

	private \Minime\Annotations\Reader $reader;
	private array $namespace;
	private \Composer\Autoload\ClassLoader $classLoader;
	private \Andesite\Attachment\Repository $attachmentRepository;

	public function getGhosts(){ return CodeFinder::Service()->Psr4ClassSeeker($this->namespace['ghost']); }
	public function getAttachmentRepository(): \Andesite\Attachment\Repository{ return $this->attachmentRepository; }
	public function getNamespace(){ return $this->namespace; }

	public function __construct(Reader $reader, ClassLoader $classLoader){
		$this->reader = $reader;
		$this->classLoader = $classLoader;
	}

	public function setup($config){
		$this->namespace = $config['namespace'];
		$this->attachmentConfig = $config['attachment-config'];
	}

	protected function load(ConnectionFactory $factory){
		$this->attachmentRepository = new \Andesite\Attachment\Repository($this->attachmentConfig);
		spl_autoload_register (function ($class){
			if(file_exists($file = $this->classLoader->findFile($class))){
				require_once $file;
				if (is_subclass_of($class, Ghost::class) && !( new ReflectionClass($class) )->isAbstract()) $class::init();
			}
		},true, true);

	}


	public function routeThumbnail(Router $router){
		$this->attachmentRepository->routeThumbnails($router);
	}
}