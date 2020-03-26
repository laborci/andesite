<?php namespace Andesite\Core\Session;

use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\SharedService;

class SessionEvent extends Session implements SharedService{

	use Service;

	protected $events = [];

	public function set($event, $data = true){
		if(!is_array($this->events)) $this->events = [];
		if(!array_key_exists($event, $this->events))$this->events[$event] = $data;
		$this->flush();
	}

	public function get($event){
		if(!is_array($this->events)) $this->events = [];
		if(!count($this->events) || !array_key_exists($event, $this->events)) return false;
		$events = $this->events[$event];
		unset($this->events[$event]);
		$this->flush();
		return $events;
	}

}