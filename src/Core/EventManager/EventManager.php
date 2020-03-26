<?php namespace Andesite\Core\EventManager;

use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\SharedService;

class EventManager implements SharedService{

	use Service;

	protected $listeners = [];
	protected $firstListener = [];
	protected $finalListener = [];

	protected function addListener($event, callable $handler){
		if (!array_key_exists($event, $this->listeners)) $this->listeners[$event] = [];
		$this->listeners[$event][] = $handler;
	}

	protected function addFirstListener($event, callable $handler){
		if (array_key_exists($event, $this->firstListener)) throw new \Exception('Multiple Event firstListener declarations');
		$this->firstListener[$event] = $handler;
	}

	protected function addFinalListener($event, callable $handler){
		if (array_key_exists($event, $this->finalListener)) throw new \Exception('Multiple Event finalListener declarations');
		$this->finalListener[$event] = $handler;
	}

	protected function fireEvent(string $event, $data = null){
		if (array_key_exists($event, $this->firstListener)) $this->firstListener[$event]($data);
		if (array_key_exists($event, $this->listeners)){
			foreach ($this->listeners[$event] as $handler){
				if ($handler($data) === false) break;
			}
		}
		if (array_key_exists($event, $this->finalListener)) $this->finalListener[$event]($data);
	}

	static function fire(string $event, $data = null){ static::Service()->fireEvent($event, $data); }
	static function listen(string $event, callable $handler){ static::Service()->addListener($event, $handler); }
	static function first(string $event, callable $handler){ static::Service()->addFirstListener($event, $handler); }
	static function final(string $event, callable $handler){ static::Service()->addFinalListener($event, $handler); }

}