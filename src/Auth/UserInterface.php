<?php namespace Andesite\Auth;

interface UserInterface{
	public function getIdentifier():int ;
	public function checkPassword($password): bool;
	public function hasRole($role = null): bool;
}
