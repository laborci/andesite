<?php namespace Andesite\Core\Module;

use Andesite\Core\ServiceManager\SharedService;

abstract class Module implements SharedService{
	public static function Module($loaded = true): ?self{ return ModuleManager::get(get_called_class(), $loaded); }
}