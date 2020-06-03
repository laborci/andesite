<?php namespace Andesite\Codex\Middleware;


use Andesite\Codex\Interfaces\AuthInterface;
use Andesite\Mission\Web\Pipeline\Middleware;

class AuthCheck extends Middleware {

	protected $auth;

	public function __construct(AuthInterface $auth) {
		$this->auth = $auth;
	}

	protected function run() {
		$responder = $this->getArgumentsBag()->get('responder');
		if (!$this->auth->isAuthenticated()){
			$this->auth->logout($this->getResponse());
			$this->break($responder);
		} else {
			$this->next();
		}
	}

	static public function config($responder){
		return ['responder'=>$responder];
	}


}