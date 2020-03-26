<?php namespace Andesite\Zuul\Interfaces;

interface AuthSessionInterface {

	public function setUserId($userId);
	public function getUserId();
	public function forget();


}