<?php namespace Andesite\Codex\Form;

use Andesite\Codex\Form\FormHandler\FormHandler;
use Andesite\Codex\Form\ListHandler\ListHandler;
use Andesite\Codex\Interfaces\DataProviderInterface;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\SharedService;

abstract class AdminDescriptor implements SharedService{

	use Service;

	abstract protected function listHandler(ListHandler $codexList);
	abstract protected function formHandler(FormHandler $codexForm);
	abstract protected function createDataProvider(): DataProviderInterface;
	abstract protected function decorator(FormDecorator $decorator);

	public function getDataProvider(): DataProviderInterface{ return $this->createDataProvider(); }
	public function getDecorator(): FormDecorator{
		$decorator = new FormDecorator('fal fa-infinite', 'Andesite Codex', (new \ReflectionClass($this))->getShortName());
		$this->decorator($decorator);
		return $decorator;
	}
	public function getListHandler(): ListHandler{
		$listhandler = new ListHandler($this);
		$this->listHandler($listhandler);
		return $listhandler;
	}
	public function getFormHandler(): FormHandler{
		$formhandler = new FormHandler($this);
		$this->formHandler($formhandler);
		return $formhandler;
	}
}

