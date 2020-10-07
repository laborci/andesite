<?php namespace Andesite\Core\ServiceManager;

trait Service{
	/**
	 * @return static
	 */
	public static function Service(): self{ return ServiceContainer::get(get_called_class()); }
}