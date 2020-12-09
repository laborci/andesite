<?php namespace Andesite\Core\ServiceManager;

class ServiceFactory{

	private $name;
	private $shared = false;
	private $factory = null;
	private $service = null;
	private $sharedService = null;
	private $args;

	public function __construct(string $name, $shared, $factory, $args){
		$this->name = $name;
		if (is_null($shared)) $this->sharedService = $factory;
		else{
			$this->args = $args;
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
				$service = $class::factory(...$this->args);
			}else{
				$constructor = $reflection->getConstructor();
				$arguments = is_null($constructor) ? [] : array_map(function (\ReflectionParameter $parameter){
					return ServiceContainer::get($parameter->getType()->getName());
				}, $constructor->getParameters());
				$service = new $class(...$arguments);
			}
		}else{
			$service = ( $this->factory )(...$this->args);
		}

		if ($this->shared){
			$this->sharedService = $service;
		}

		return $service;
	}

}