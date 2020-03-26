<?php namespace Andesite\Zuul\RoleManager;

class GroupManager{

	protected $groups;

	public function __construct($groupsClass){
		$this->groups = (new \ReflectionClass($groupsClass))->getConstants();
	}

	public function resolve($groups){
		$groups = is_array($groups) ? $groups : [$groups];
		$roles = [];
		foreach ($groups as $group){
			if (array_key_exists($group, $this->groups) && is_array($this->groups[$group])){
				array_walk_recursive($this->groups[$group], function ($role) use (&$roles){
					if($role[0] === '-') unset($roles[substr($role,1)]);
					else $roles[$role] = true;
				});
			}
		}
		return $roles;
	}

	public function getGroups(){
		return array_keys($this->groups);
	}

}