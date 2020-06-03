<?php namespace Andesite\Codex\Page;

use Andesite\Core\Boot\Andesite;
use Andesite\TwigResponder\Responder\SmartPageResponder;

/**
 * @title Admin
 * @template "@codex/Index.twig"
 */
class Index extends SmartPageResponder {

	function prepare() {
		/** @var \Andesite\Codex\CodexMission $mission */
		$mission = Andesite::mission();
		$this->css = [$mission->getFrontendPrefix().'app.css'];
		$this->js = [$mission->getFrontendPrefix().'app.js'];
		$this->title = $mission->getTitle();

		$this->getDataBag()->set('admin', [
			'title'             => $mission->getTitle(),
			'icon'              => $mission->getIcon(),
			'login-placeholder' => $mission->getLoginPlaceholder()
		]);
		$this->set('user', $mission->getUserName());
		$this->set('avatar', $mission->getUserAvatar());
	}

}