<?php namespace Andesite\Codex\Action;

use Andesite\Codex\Interfaces\AuthInterface;
use Andesite\Mission\Web\Responder\JsonResponder;

class Logout extends JsonResponder{

	protected $auth;

	public function __construct(AuthInterface $auth){
		$this->auth = $auth;
	}

	protected function respond(){
		$this->auth->logout();
	}

}