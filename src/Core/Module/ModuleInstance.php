<?php namespace Andesite\Core\Module;

class ModuleInstance{

	public $name;
	public $module;
	public $dependencies = [];
	/** @var bool|\ReflectionMethod */
	public $load = false;
	/** @var bool|\ReflectionMethod */
	public $run = false;
	public $running = false;
	public $config;
	public $loaded = false;
	public $setup = false;

	public function __construct(Module $module, $config){
		$this->config = $config;
		$this->module = $module;

		$this->name = get_class($module);

		$reflection = new \ReflectionClass($module);
		if ($reflection->hasMethod('load')){
			$this->load = $reflection->getMethod('load');
			$params = $reflection->getMethod('load')->getParameters();
			foreach ($params as $param){
				$this->dependencies[$param->getClass()->name] = $param->isOptional();
			}
		}

		if ($reflection->hasMethod('setup')){
			$this->setup = $reflection->getMethod('setup');
		}

		if ($reflection->hasMethod('run')){
			$this->run = $reflection->getMethod('run');
		}
	}

	public function setup(){
		if ($this->setup && !$this->running){
			$this->setup->setAccessible(true);
			$this->setup->invoke($this->module, $this->config);
			$this->setup->setAccessible(false);
		}
	}

	public function load(...$dependencies){
		if ($this->load && !$this->running){
			$this->load->setAccessible(true);
			$this->load->invoke($this->module, ...$dependencies);
			$this->load->setAccessible(false);
		}
	}
	public function run(){
		if ($this->run && !$this->running){
			$this->run->setAccessible(true);
			$this->run->invoke($this->module, $this->config);
			$this->run->setAccessible(false);
			$this->running = true;
		}
	}
}