<?php namespace Andesite\Core\ServiceManager;

trait Service{
	public static function Service(): self{ return ServiceContainer::get(get_called_class()); }
}