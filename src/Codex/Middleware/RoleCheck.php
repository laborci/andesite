<?php namespace Andesite\Codex\Middleware;

use Andesite\Codex\Interfaces\AuthInterface;
use Andesite\Mission\Web\Pipeline\Middleware;

class RoleCheck extends Middleware {

	protected $auth;

	public function __construct(AuthInterface $auth) {
		$this->auth = $auth;
	}

	protected function run() {

		$responder = $this->getArgumentsBag()->get('responder');
		$role = $this->getArgumentsBag()->get('role');
		$logoutOnFail = $this->getArgumentsBag()->get('logout-on-fail');

		if (!$this->auth->hasRole($role)) {
			if($logoutOnFail) $this->auth->logout();
			$this->break($responder);
		} else {
			$this->next();
		}
	}

	static public function config($responder, $role, $logoutOnFail){
		return[
			'responder' => $responder,
			'role'=>$role,
			'logout-on-fail'=>$logoutOnFail
		];
	}

}