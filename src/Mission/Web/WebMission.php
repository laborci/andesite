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
		$this->route($this->router);
		die();
	}

	abstract public function route(Router $router);

}