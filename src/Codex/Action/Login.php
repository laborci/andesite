<?php namespace Andesite\Codex\Action;

use Andesite\Codex\Interfaces\AuthInterface;
use Andesite\Mission\Web\Responder\JsonResponder;

class Login extends JsonResponder{

	protected $auth;

	public function __construct(AuthInterface $auth){ $this->auth = $auth; }

	protected function respond(){ if (!$this->auth->login($this->getRequestBag()->get('login'), $this->getRequestBag()->get('password'))) $this->getResponse()->setStatusCode('401'); }

}