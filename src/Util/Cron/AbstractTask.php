<?php namespace Andesite\Util\Cron;

abstract class AbstractTask{

	abstract public function run($config);

}