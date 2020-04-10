<?php namespace Andesite\Core\Env;

use Andesite\Core\Boot\Andesite;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Core\ServiceManager\SharedService;
use Andesite\Util\DotArray\DotArray;

class Env implements SharedService {

	use Service;

	protected $env = [];

	public function __construct(EnvLoader $envLoader){
		if(Andesite::Service()->isDevMode()) $envLoader->rebuildCache(false);
		$this->load(getenv('env-file'));
	}

	public function reload($force = false){
		$envLoader = ServiceContainer::get(EnvLoader::class);
		$envLoader->rebuildCache($force);
		$this->load(getenv('env-file'));
	}

	public function load($file) {$this->store(include $file);}
	public function store($env) {$this->env = $env;}
	public function get($key = null) {
		if($key === null) return $this->env;
		if(array_key_exists($key, $this->env)) return $this->env[$key];
		return DotArray::get($this->env, $key, null);
	}
	public function set($key, $value) { DotArray::set($this->env, $key, $value); }

}