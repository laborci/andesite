<?php namespace Andesite\Zuul\Auth;

use Andesite\Core\Session\Session;
use Andesite\Zuul\Interfaces\AuthSessionInterface;

class AuthSession extends Session implements AuthSessionInterface {

	public $userId;
	public function setUserId($userId) {$this->userId = $userId; $this->flush(); }
	public function getUserId() { return $this->userId; }

}