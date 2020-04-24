<?php namespace Andesite\Core\ServiceManager;

class ServiceContainer{

	/** @var ServiceFactory[] */
	protected $services = [];

	protected static $instance;
	protected static function instance(){ return is_null(static::$instance) ? static::$instance = new static() : static::$instance; }
	protected function __construct(){ }

	public static function bind($name, $service, ...$args): ServiceFactory{ return static::instance()->set($name, false, $service, $args); }
	public static function shared($name, $service, ...$args): ServiceFactory{ return static::instance()->set($name, true, $service, $args); }
	public static function value($name, $value): ServiceFactory{ return static::instance()->set($name, null, $value); }
	public static function get($name){ return static::instance()->_get($name); }

	protected function set($name, $shared, $factory, $args = []){
		$service = new ServiceFactory($name, $shared, $factory, $args);
		$this->services[$name] = $service;
		return $service;
	}

	protected function _get($name){
		if (!array_key_exists($name, $this->services)){
			try{
				$reflection = new \ReflectionClass($name);
				if (!$reflection->isInstantiable()) return null;
				$this->set($name, $reflection->implementsInterface(SharedService::class), $name);
			}catch (\Exception $e){
				trigger_error('Service or autoservice "' . $name . '" not found. Exception:' . $e->getMessage());
			}
		}
		return $this->services[$name]->get();
	}

	public static function dump(){ dump(static::instance()->services); }
}