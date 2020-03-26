<?php namespace Andesite\Attachment;

use Andesite\Core\Module\Module;

class AttachmentRepository extends Module{

	private $repository;
	private $pool = [];

	protected function setup($config){
		$this->repository = $config;
	}

	public function getStorage($storage, $name){
		$key = $storage.'.'.$name;
		if(!array_key_exists($key, $this->pool)){
			$this->pool[$key] = new AttachmentStorage($storage, $this->repository[$name]);
		}
		return $this->pool[$key];
	}

	public function getConfig($name){
		return $this->repository[$name];
	}

	public function routeThumbnails($router, $name){
		$router->get($this->repository[$name]['thumbnail']['url'].'/*', ThumbnailResponder::class, ['config'=>$this->repository[$name]])();
	}
}