<?php namespace Andesite\Codex\Interfaces;

use Andesite\Auth\UserInterface;
use Symfony\Component\HttpFoundation\Response;
interface AuthInterface{
	public function hasRole($role):bool;
	public function logout();
	public function login($login, $password);
	public function isAuthenticated():bool;
}