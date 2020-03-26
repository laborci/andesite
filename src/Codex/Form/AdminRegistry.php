<?php namespace Andesite\Codex\Form;

class AdminRegistry{

	protected $admins = [];

	public function registerForm($form){
		$this->admins[(new \ReflectionClass($form))->getShortName()] = $form;
	}

	public function get($name):AdminDescriptor{
		/** @var \Andesite\Codex\Form\AdminDescriptor $form */
		/** @var \Andesite\Codex\Form\AdminDescriptor $codex */
		$form = $this->admins[$name];
		$codex = $form::Service();
		return $codex;
	}
}