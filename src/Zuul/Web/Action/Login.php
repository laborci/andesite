<?php namespace Andesite\Zuul\Web\Action;

use Andesite\Mission\Web\Responder\JsonResponder;
use Andesite\Zuul\Interfaces\AuthServiceInterface;

class Login extends JsonResponder{

	protected $authService;

	public function __construct(AuthServiceInterface $authService){
		$this->authService = $authService;
	}

	protected function respond($role = null){
		if (!$this->authService->login($this->getRequestBag()->get('login'), $this->getRequestBag()->get('password'), $role)){
			$this->getResponse()->setStatusCode('401');
		}
	}

}