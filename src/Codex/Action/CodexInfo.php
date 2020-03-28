<?php namespace Andesite\Codex\Action;

class CodexInfo extends Responder{
	
	protected function codexRespond(): ?array{
		$info =  [
			'header' => ['icon'=>$this->formDecorator->getIconHeader(), 'title'=>$this->formDecorator->getTitle()],
			'urlBase'=> $this->formDecorator->getUrl(),
			'list'   => $this->adminDescriptor->getListHandler(),
		];
		return $info;
	}
}
