<?php namespace Andesite\Auth;

use Andesite\Core\Env\Env;
use Andesite\Auth\RoleManager\RoleManager;


/**
 * @property-read \Andesite\Attachment\Collection $avatar
 */
trait CommonUser{
	protected function setPassword($value){ $this->password = password_hash($value, PASSWORD_BCRYPT); }
	public function checkPassword($password): bool{ return password_verify($password, $this->password); }
	public function getIdentifier(): int{ return $this->id; }
	public function hasRole($role = null): bool{ return ( is_null($role) || array_key_exists($role, RoleManager::Module()->resolveGroups($this->groups)) ); }
	public function getAvatar($size, $default = null): ?string{
		return $this->avatar->count ? $this->avatar->first->thumbnail->crop($size, $size)->url : $default;
	}
}

