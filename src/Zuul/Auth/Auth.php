<?php namespace Andesite\Zuul\Auth;

use Andesite\Core\Module\Module;
use Andesite\Zuul\Interfaces\AuthenticableInterface;
use Andesite\Zuul\Interfaces\AuthRepositoryInterface;
use Andesite\Zuul\Interfaces\AuthServiceInterface;
use Andesite\Zuul\Interfaces\AuthSessionInterface;
use Andesite\Zuul\Interfaces\AutoLoginRepositoryInterface;
use Andesite\Zuul\Interfaces\WhoAmIInterface;
use Andesite\Core\ServiceManager\ServiceContainer;

class Auth extends Module{

	public function setup($config){
		ServiceContainer::shared(AuthServiceInterface::class, AuthService::class);
		ServiceContainer::shared(AuthSessionInterface::class, AuthSession::class);
		ServiceContainer::shared(WhoAmIInterface::class, WhoAmI::class);

		ServiceContainer::shared(AuthenticableInterface::class, $config['authenticable']);
		ServiceContainer::shared(AuthRepositoryInterface::class, $config['auth-repository']);
		if(!empty($config['autologin-repository'])) ServiceContainer::shared(AutoLoginRepositoryInterface::class, $config['autologin-repository']);
	}
}