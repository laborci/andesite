<?php namespace Andesite\Auth\RoleManager;

use Andesite\Core\Module\Module;

class RoleManager extends Module{
	protected $groupManager;
	public function setup($config){$this->groupManager = new GroupManager($config['groups']); }
	public function resolveGroups($groups){ return $this->groupManager->resolve($groups); }
	public function getGroups(){ return $this->groupManager->getGroups(); }
}