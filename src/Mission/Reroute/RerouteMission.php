<?php namespace Andesite\Mission\Reroute;

use Andesite\Mission\Mission;
use Symfony\Component\HttpFoundation\Request;

class RerouteMission extends Mission{

	protected $config;
	protected function run($config){
		die(header('location:' . Request::createFromGlobals()->getScheme() . '://' . $this->config['reroute']));
	}
}