<?php namespace Andesite\Zuul\Interfaces;

interface AuthenticableInterface {
	public function getId():int;
	public function checkPassword($password):bool;
	public function checkRole($role):bool;
}