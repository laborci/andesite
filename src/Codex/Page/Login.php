<?php namespace Andesite\Codex\Page;

use Andesite\Codex\Module;
use Andesite\Core\Boot\Andesite;
use Andesite\TwigResponder\Responder\SmartPageResponder;

/**
 * @title     Admin
 * @bodyclass login
 * @template "@codex/Login.twig"
 */
class Login extends SmartPageResponder{

	function prepare(){

		/** @var \Andesite\Codex\CodexMission $mission */
		$mission = Andesite::mission();

		$this->css = [$mission->getFrontendPrefix().'login.css'];
		$this->js = [$mission->getFrontendPrefix().'login.js'];
		$this->title = $mission->getTitle();

		$this->getDataBag()->set('admin', [
			'title'             => $mission->getTitle(),
			'icon'              => $mission->getIcon(),
			'login-placeholder' => $mission->getLoginPlaceholder()
		]);
	}

}