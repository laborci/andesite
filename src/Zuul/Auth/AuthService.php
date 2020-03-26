<?php namespace Andesite\Zuul\Auth;

use Andesite\Core\EventManager\EventManager;
use Andesite\Zuul\Interfaces\AuthenticableInterface;
use Andesite\Zuul\Interfaces\AuthRepositoryInterface;
use Andesite\Zuul\Interfaces\AuthServiceInterface;
use Andesite\Zuul\Interfaces\AuthSessionInterface;

class AuthService implements AuthServiceInterface{

	protected $session;
	protected $repository;

	public function __construct(AuthSessionInterface $session, AuthRepositoryInterface $repository){
		$this->session = $session;
		$this->repository = $repository;
	}

	public function login($login, $password, $role = null): bool{
		$user = $this->repository->authLoginLookup($login);

		if(!$user){
			EventManager::fire(Event::LOGIN_ERROR_USER_NOT_FOUND, $login);
			return false;
		}

		if(!$user->checkPassword($password)){
			EventManager::fire(Event::LOGIN_ERROR_WRONG_PASSWORD, $login);
			return false;
		}

		if(!(is_null($role) || $user->checkRole($role))){
			EventManager::fire(Event::LOGIN_ERROR_WRONG_PERMISSION, $login);
			return false;
		}
		$this->registerAuthSession($user);
		return true;
	}

	public function logout(){ $this->clearAuthSession(); }

	public function isAuthenticated(): bool{
		return (bool)$this->session->getUserId();
	}
	public function getAuthenticatedId(): int{ return $this->session->getUserId(); }

	public function checkRole($role): bool{
		if(!$this->isAuthenticated()) return false;
		if(!$this->repository->authLookup($this->session->getUserId())) return false;
		return $this->repository->authLookup($this->session->getUserId())->checkRole($role);
	}


	public function registerAuthSession(AuthenticableInterface $user){ $this->session->setUserId($user->getId());}
	protected function clearAuthSession(){$this->session->forget(); }


}