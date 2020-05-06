<?php

use Andesite\Core\ServiceManager\ServiceContainer;
use \Andesite\Util\Dumper\DumpInterface;

if (!function_exists('dump')){
	function dump($message){
		( $dumper = ServiceContainer::get(DumpInterface::class) ) ? $dumper->dump($message) : null;
	}
}
