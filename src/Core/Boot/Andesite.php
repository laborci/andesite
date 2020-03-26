<?php namespace Andesite\Core\Boot;

use Andesite\Core\Env\Env;
use Andesite\Core\Module\ModuleManager;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Mission\Mission;
use Composer\Autoload\ClassLoader;
use Minime\Annotations\Cache\FileCache;
use Minime\Annotations\Parser;
use Minime\Annotations\Reader;
use Symfony\Component\HttpFoundation\Request;

class Andesite{

	use Service;

	private $mission;

	public static function mission(): Mission{
		/** @var Mission $mission */
		$mission = ModuleManager::get(static::Service()->mission);
		return $mission;
	}

	public static function setup($root, $ini, $env, ClassLoader $classLoader){
		putenv('root=' . realpath($root) . '/');
		putenv('context=' . ( http_response_code() ? 'WEB' : 'CLI' ));
		putenv('env-file=' . getenv('root') . $env);
		putenv('ini-path=' . getenv('root') . dirname($ini) . '/');
		putenv('ini-file=' . basename($ini));
		ServiceContainer::value(ClassLoader::class, $classLoader);
		ServiceContainer::value(Request::class, Request::createFromGlobals());
		new static();
	}

	public function __construct(){

		/* Register Andesite service */
		ServiceContainer::value(Andesite::class, $this);

		/** @var Env $env */
		$env = ServiceContainer::get(Env::class);

		/* Setup env */
		$env->set('root', getenv('root'));
		$env->set('sys.context', getenv('context'));

		$annotationReaderCache = $env->get('path.annotation-reader-cache');
		$moduleAliases = $env->get('module.aliases');
		$moduleConfigs = $env->get('module.configs');
		$modules = $env->get('sys.modules');
		$timezone = $env->get('sys.timezone');
		$context = $env->get('sys.context');
		$missions = $env->get('sys.missions');
		$startup = $env->get('sys.startup');

		/* Setup running environment */
		date_default_timezone_set($timezone);
		ob_start();
		if ($context === 'WEB') session_start();

		/* Register base services */
		ServiceContainer::shared(Reader::class, function () use ($annotationReaderCache){ return new Reader(new Parser(), new FileCache($annotationReaderCache)); });

		/* Setup modules */
		ModuleManager::setAliases($moduleAliases);
		ModuleManager::setConfigs($moduleConfigs);

		/* Run Startup */
		foreach ($startup as $module => $moduleConfig) ModuleManager::register($module, $moduleConfig);
		ModuleManager::load();



		/* Load Modules */
		foreach ($modules as $module => $moduleConfig) ModuleManager::register($module, $moduleConfig);

		/* Find and launch active mission */
		if (is_array($missions)){
			$host = ServiceContainer::get(Request::class)->getHttpHost();
			foreach ($missions as $mission){
				$patterns = is_array($mission['pattern']) ? $mission['pattern'] : [$mission['pattern']];
				foreach ($patterns as $pattern) if (fnmatch($pattern, $host)){
					if (array_key_exists('modules', $mission) && is_array($mission['modules'])) foreach ($mission['modules'] as $module => $config){
						ModuleManager::register($module, $config);
					}
					$this->mission = $mission['mission'];
					ModuleManager::register($mission['mission'], array_key_exists('config', $mission) ? $mission['config'] : []);
				}
			}
		}
		ModuleManager::load();
	}

}

