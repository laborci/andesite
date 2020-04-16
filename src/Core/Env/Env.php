<?php namespace Andesite\Core\Env;

use Andesite\Core\Boot\Andesite;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Core\ServiceManager\SharedService;
use Andesite\Util\DotArray\Dot;

class Env implements SharedService {

	use Service;

	protected $env = [];

	public function __construct(){
		if(Andesite::Service()->isDevMode()) EnvLoader::Service()->rebuildCache(false);
		$this->load(getenv('env-file'));
	}

	public function reload($force = false){
		EnvLoader::Service()->rebuildCache($force);
		$this->load(getenv('env-file'));
	}

	public function load($file) {
		if(!file_exists($file)) EnvLoader::Service()->rebuildCache(true);
		$this->store(include $file);
	}
	public function store($env) {$this->env = $env;}
	public function get($key = null) {
		if($key === null) return $this->env;
		if(array_key_exists($key, $this->env)) return $this->env[$key];
		return Dot::get($this->env, $key, null);
	}
	public function set($key, $value) { Dot::set($this->env, $key, $value); }

}