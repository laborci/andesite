<?php namespace Andesite\Auth;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;

class JWT{

	private Key $key;
	private Sha256 $signer;
	private Configuration $config;

	/**
	 * @param string $key
	 */
	public function __construct(string $key){
		$this->signer = new Sha256();
		$this->key = new Key($key);
		$this->config = Configuration::forSymmetricSigner($this->signer, $this->key);
	}

	/**
	 * @param string $timeout
	 * @param array  $claims
	 * @return string
	 */
	public function create(string $timeout, array $claims = []): string{
		$builder = $this->config->createBuilder()
			->issuedAt(new \DateTimeImmutable())
			->expiresAt(( new \DateTimeImmutable() )->modify($timeout));
		foreach ($claims as $key => $value) $builder->withClaim($key, $value);
		return $builder->getToken($this->signer, $this->key)->__toString();
	}

	/**
	 * @param string $token
	 * @return \Lcobucci\JWT\Token\Plain
	 */
	public function parse(string $token){ return $this->config->getParser()->parse($token); }

}