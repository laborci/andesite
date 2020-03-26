<?php namespace Andesite\Zuul\Interfaces;

interface AuthServiceInterface {

	public function isAuthenticated():bool;
	public function getAuthenticatedId():int;
	public function login($login, $password, $role = null): bool;
	public function checkRole($role): bool;
	public function logout();
	public function registerAuthSession(AuthenticableInterface $user);

}