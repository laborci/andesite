<?php namespace Andesite\Mission;

use Andesite\Core\Module\Module;

abstract class Mission extends Module{
	abstract protected function run($config);
}