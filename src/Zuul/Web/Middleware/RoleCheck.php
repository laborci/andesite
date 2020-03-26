<?php namespace Andesite\Zuul\Web\Middleware;

use Andesite\Mission\Web\Pipeline\Middleware;
use Andesite\Zuul\Interfaces\AuthServiceInterface;

class RoleCheck extends Middleware {

	protected $authService;

	public function __construct(AuthServiceInterface $authService) {
		$this->authService = $authService;
	}

	protected function run() {

		$responder = $this->getArgumentsBag()->get('responder');
		$role = $this->getArgumentsBag()->get('role');
		$logoutOnFail = $this->getArgumentsBag()->get('logout-on-fail');

		if (!$this->authService->checkRole($role)) {
			if($logoutOnFail) $this->authService->logout();
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
