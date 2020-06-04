<?php namespace Andesite\Auth;

use Andesite\Core\Session\Session;

class AuthSession extends Session{
	public $id;

	public function setUserToken($id){
		$this->id = $id;
		$this->flush();
	}

	public function getUserToken(){
		return $this->id;
	}
}