<?php namespace Andesite\Attachment;

use Andesite\Core\Module\Module;
use Andesite\Mission\Web\Routing\Router;

class AttachmentRepository extends Module{

	private array $repository;
	/** @var \Andesite\Attachment\AttachmentStorage[]  */
	private $pool = [];

	private function setup(array $config){
		$this->repository = $config;
	}

	public function getStorage(string $storage, string $name):AttachmentStorage{
		$key = $storage.'.'.$name;
		if(!array_key_exists($key, $this->pool)){
			$this->pool[$key] = new AttachmentStorage($storage, $this->repository[$name]);
		}
		return $this->pool[$key];
	}

	public function getConfig(string $name):array {
		return $this->repository[$name];
	}

	public function routeThumbnails(Router $router, string $name){
		$router->get($this->repository[$name]['thumbnail']['url'].'/*', ThumbnailResponder::class, ['config'=>$this->repository[$name]])();
	}
}