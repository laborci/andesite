<?php namespace Andesite\Zuul\Web\Action;

use Andesite\Mission\Web\Responder\JsonResponder;
use Andesite\Zuul\Interfaces\AuthServiceInterface;

class Logout extends JsonResponder{

	protected $authService;

	public function __construct(AuthServiceInterface $authService){
		$this->authService = $authService;
	}

	protected function respond(){
		$this->authService->logout();
	}

}