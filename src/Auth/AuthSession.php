<?php namespace Andesite\Auth;

use Andesite\Core\Session\Session;

class AuthSession extends Session{
	public $id;

	public function setUserId($id){
		$this->id = $id;
		$this->flush();
	}

	public function getUserId(){
		return $this->id;
	}
}