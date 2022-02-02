<?php namespace Andesite\Auth;

use Andesite\Codex\Interfaces\AuthInterface;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Core\ServiceManager\SharedService;
use Application\Ghost\User;
use DateTime;
use Lcobucci\JWT\Token\DataSet;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAuth implements SharedService, AuthInterface{

	use Service;

	protected ?AuthSession $session;
	protected ?UserInterface $user = null;
	protected string $autologinTimeOut;
	protected string $autologinCookie;
	protected string $jwtKey;

	public function __construct(AuthSession $session){
		$this->session = $session;
		$this->jwt = new JWT($this->jwtKey);
		$this->restoreBySession();
	}

	#region Abstracts
	abstract public function whoAmI(): ?UserInterface;

	abstract protected function find($login): ?UserInterface;

	abstract protected function create($id): ?UserInterface;

	abstract protected function hasLoginAccess(UserInterface $user): bool;

	abstract protected function validateToken(DataSet $token): bool;

	abstract protected function createTokenData(): array;
	#endregion

	# region Authentication
	public function login($login, $password): ?UserInterface{
		$user = $this->find($login);
		$user = !is_null($user) && $user->checkPassword($password) ? $user : null;
		return $this->register($user);
	}

	public function restore($id): ?UserInterface{
		$user = $this->create($id);
		return $this->register($user);
	}

	public function restoreBySession(): ?UserInterface{
		$token = $this->session->getUserToken();
		$user = ( $token ) ? $this->restoreByToken($token) : null;
		if (is_null($user)) $this->session->forget();

		return $user;
	}

	public function restoreByToken(string $token): ?UserInterface{
		$token = $this->jwt->parse($token);
		if (is_null($token)) return null;
		$claims = $token->claims();
		return ( !$token->isExpired(new \DateTime()) && $this->validateToken($claims) ) ? $this->restore($claims->get('IDENTIFIER')) : null;
	}

	protected function register(?UserInterface $user): ?UserInterface{
		if (is_null($user) || $this->hasLoginAccess($user) !== true) return null;
		$this->user = $user;
		$token = $this->createAuthToken("+ 30 days");
		$this->session->setUserToken($token);
		return $user;
	}

	public function logout(): void{
		$response = ServiceContainer::get(Response::class);
		$this->session->forget();
		$this->clearAutologin($response);
	}
	# endregion

	#region Autologin
	public function autologin(): ?UserInterface{
		$response = ServiceContainer::get(Response::class);
		$request = ServiceContainer::get(Request::class);

		if (!is_null($this->user)) return $this->user;

		$token = $request->cookies->get($this->autologinCookie);
		if (is_null($token)) return null;

		$user = $this->restoreByToken($token);

		if (is_null($user)) $this->clearAutologin($response);
		return $user;
	}

	public function setAutologin(): bool{
		/** @var Response $response */
		$response = ServiceContainer::get(Response::class);
		return !is_null($this->user) ? $response->headers->setCookie(new Cookie($this->autologinCookie, $this->createAuthToken($this->autologinTimeOut), strtotime('now ' . $this->autologinTimeOut))) || true : false;
	}

	public function clearAutologin(): void{
		/** @var Response $response */
		$response = ServiceContainer::get(Response::class);
		$response->headers->clearCookie($this->autologinCookie);
	}
	#endregion

	# region WhoAmI proxy
	public function isAuthenticated(): bool{ return !is_null($this->user); }

	public function hasRole($role): bool{ return $this->isAuthenticated() && $this->user->hasRole($role); }
	# endregion

	# region Token proxy
	public function createAuthToken(string $timeout): ?string{
		return $this->isAuthenticated() ? $this->createToken($timeout, array_merge($this->createTokenData(), ['IDENTIFIER' => $this->user->getIdentifier()])) : null;
	}

	public function getTokenClaims(string $token): ?\Lcobucci\JWT\Token\DataSet{
		$token = $this->jwt->parse($token);
		if (is_null($token)) return null;
		return !( $token = $this->jwt->parse($token) )->isExpired(new DateTime()) ? $token->claims() : null;
	}

	public function createToken(string $timeout, array $data = []): string{
		return $this->jwt->create($timeout, $data);
	}

	# endregion

}