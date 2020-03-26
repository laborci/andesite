<?php namespace Andesite\Core\ServiceManager;

class ServiceFactory{

	protected $name;
	protected $shared = false;
	protected $factory = null;
	protected $service = null;
	protected $sharedService = null;

	public function __construct(string $name, $shared, $factory){
		$this->name = $name;
		if (is_null($shared)) $this->sharedService = $factory;
		else{
			$this->shared = $shared;
			$this->factory = $factory;
		}
	}

	public function get(){

		if (!is_null($this->sharedService)){
			return $this->sharedService;
		}

		if (is_string($this->factory)){
			$class = $this->factory;
			$reflection = new \ReflectionClass($class);
			if ($reflection->implementsInterface(SelfFactoryService::class)){
				$service = $class::factory();
			}else{
				$constructor = $reflection->getConstructor();
				$arguments = is_null($constructor) ? [] : array_map(function (\ReflectionParameter $parameter){
					return ServiceContainer::get($parameter->getClass()->name);
				}, $constructor->getParameters());
				$service = new $class(...$arguments);
			}
		}else{
			$service = ( $this->factory )($this->name);
		}

		if ($this->shared){
			$this->sharedService = $service;
		}

		return $service;
	}

}