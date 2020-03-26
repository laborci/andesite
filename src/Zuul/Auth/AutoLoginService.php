<?php namespace Andesite\Zuul\Auth;

use Andesite\Zuul\Interfaces\AuthRepositoryInterface;
use Andesite\Zuul\Interfaces\AuthServiceInterface;
use Andesite\Zuul\Interfaces\AutoLoginRepositoryInterface;
use Andesite\Core\EventManager\EventManager;
use Andesite\Core\ServiceManager\SharedService;

class AutoLoginService implements SharedService{

	protected $authService;
	protected $repository;
	/** @var \Application\Service\Auth\AuthRepository */
	private $authRepository;
	/** @var \Application\Service\Auth\AutoLoginRepository */
	private $autoLoginRepository;

	public function __construct(AuthServiceInterface $authService, AuthRepositoryInterface $authRepository, AutoLoginRepositoryInterface $autoLoginRepository){
		$this->authService = $authService;
		$this->authRepository = $authRepository;
		$this->autoLoginRepository = $autoLoginRepository;
	}

	public function register(){
		if (!$this->authService->isAuthenticated()) return false;
		return $this->autoLoginRepository->create($this->authService->getAuthenticatedId());
	}

	public function autologin($token){
		$userId = $this->autoLoginRepository->findByToken($token);
		if (!$userId) return false;

		$user = $this->authRepository->authLookup($userId);

		if (!$user->checkRole(null)){
			$this->autoLoginRepository->delete($token);
			return false;
		}

		$this->autoLoginRepository->update($token);
		$this->authService->registerAuthSession($user);
		EventManager::fire(Event::AUTOLOGIN, $user);
		return true;
	}

	public function clear($token){ $this->autoLoginRepository->delete($token); }

}