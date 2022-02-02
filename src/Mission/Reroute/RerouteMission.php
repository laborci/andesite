<?php namespace Andesite\Mission\Reroute;

use Andesite\Mission\Mission;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class RerouteMission extends Mission{

	protected $config;
	protected function run($config){
		(new RedirectResponse('location:' . Request::createFromGlobals()->getScheme() . '://' . $config['reroute'], 301))->send();
		die();
	}
}