<?php namespace Andesite\Codex\Action;

class CodexGetForm extends Responder{

	protected function codexRespond(): ?array{

		$formHandler = $this->adminDescriptor->getFormHandler();

		return [
			'descriptor' => $formHandler,
		];
	}

}

