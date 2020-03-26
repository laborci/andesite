<?php namespace Andesite\Mission\Web;

use Andesite\Core\EventManager\EventManager;
use Andesite\Mission\Mission;
use Andesite\Mission\Web\Routing\Router;
use Andesite\Mission\Web\Routing\RoutingEvent;

abstract class WebMission extends Mission{

	/** @var Router */
	protected $router;

	public function __construct(Router $router){ $this->router = $router; }

	public function run($config){
		//EventManager::fire(RoutingEvent::BEFORE, $this->router);
		$this->route($this->router);
		//EventManager::fire(RoutingEvent::FINISHED, $this->router);
		//EventManager::fire(RoutingEvent::NOTFOUND, $this->router);
		die();
	}

	abstract public function route(Router $router);

}