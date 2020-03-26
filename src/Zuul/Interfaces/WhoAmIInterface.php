<?php namespace Andesite\Zuul\Interfaces;

interface WhoAmIInterface{
	public function checkRole($role):bool;
	public function isAuthenticated():bool;
	public function logout();
	public function __invoke():?int;
}