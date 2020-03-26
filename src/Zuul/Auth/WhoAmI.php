<?php namespace Andesite\Zuul\Auth;

use Andesite\Zuul\Interfaces\AuthServiceInterface;
use Andesite\Zuul\Interfaces\WhoAmIInterface;

class WhoAmI implements WhoAmIInterface{

	private $authService;
	public function __construct(AuthServiceInterface $authService){ $this->authService = $authService; }

	public function checkRole($role):bool{ return $this->authService->checkRole($role); }
	public function isAuthenticated():bool{ return $this->authService->isAuthenticated(); }
	public function logout(){ return $this->authService->logout(); }
	public function __invoke(): ?int{return $this->authService->getAuthenticatedId(); }

}