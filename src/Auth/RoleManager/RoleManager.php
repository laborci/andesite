<?php namespace Andesite\Auth\RoleManager;

use Andesite\Core\Module\Module;


class RoleManager extends Module{
	protected $groupManager;
	protected string $groupField;
	protected bool $multiGroup;
	protected string $userGhost;

	public function getGroupField(): string{ return $this->groupField; }
	public function isMultiGroup(): bool{ return $this->multiGroup; }
	public function getUserGhost(): string{ return $this->userGhost; }

	public function setup($config){
		$this->groupField = $config['group-field'];
		$this->multiGroup = $config['multi-group'];
		$this->userGhost = $config['user-ghost'];
		$this->groupManager = new GroupManager($config['groups']);
	}

	public function resolveGroups($user){
		$groupField = $this->groupField;
		$groups = $this->multiGroup ? $user->$groupField : [$user->$groupField];
		return $this->groupManager->resolve($groups);
	}
	
	public function getGroups(){ return $this->groupManager->getGroups(); }
}