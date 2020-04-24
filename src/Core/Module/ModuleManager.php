<?php namespace Andesite\Core\Module;

use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Core\ServiceManager\SharedService;

class ModuleManager implements SharedService{

	use Service;

	/** @var ModuleInstance[] */
	private $instances = [];
	private $aliases = [];
	private $configs = [];

	public static function get($moduleName, $loaded = true): ?Module{
		$manager = static::Service();
		if (!array_key_exists($moduleName, $manager->instances)) return null;
		$instance = $manager->instances[$moduleName];
		if ($instance->loaded === true || !$loaded) return $instance->module;
		return null;
	}

	public static function setAliases($aliases){ static::Service()->aliases = is_array($aliases) ? $aliases : []; }
	public static function setConfigs($configs){
		$manager = static::Service();
		if (is_array($configs)){
			foreach ($configs as $alias => $config){
				$manager->configs[$manager->resolveAlias($alias)] = $config;
			}
		}
	}

	private function resolveAlias($alias){ return array_key_exists($alias, $this->aliases) ? $this->aliases[$alias] : $alias; }

	public static function register($alias, $config){
		$manager = static::Service();

		$moduleName = $manager->resolveAlias($alias);

		if (array_key_exists($moduleName, $manager->instances)){
			$manager->instances[$moduleName]->config = array_merge($manager->instances[$moduleName]->config, is_array($config) ? $config : []);
			return;
		}

		if (array_key_exists($moduleName, $manager->configs)) $config = array_merge($manager->configs[$moduleName], is_array($config) ? $config : []);

		/** @var Module $module */
		$module = ServiceContainer::get($moduleName);
		$manager->instances [$moduleName] = new ModuleInstance($module, $config);
	}

	public static function add($moduleName, $config){
		static::register($moduleName, $config);
		static::load();
	}

	public static function load(){
		$manager = static::Service();
		foreach ($manager->instances as $instance) $instance->setup();
		foreach ($manager->instances as $instance) $manager->loadInstance($instance);
		foreach ($manager->instances as $instance) $instance->run();
	}

	private function loadInstance(ModuleInstance $instance){
		if ($instance->loaded === true) return $instance->module;
		if ($instance->loaded === 'loading') throw new \Exception('Module circular dependency detected');
		$instance->loaded = 'loading';
		$dependencies = [];
		foreach ($instance->dependencies as $dependency => $optional){
			if (!array_key_exists($dependency, $this->instances) && !$optional){
				throw new \Exception('Module not-optioanal dependency ' . $dependency . ' not found in ' . $instance->name);
			}
			$dependencies[] = $this->loadInstance($this->instances[$dependency]);
		}
		$instance->load(...$dependencies);
		$instance->loaded = true;
		return $instance->module;
	}
}
