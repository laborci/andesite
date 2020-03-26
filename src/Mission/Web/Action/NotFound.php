<?php namespace Andesite\Mission\Web\Action;

use Andesite\Mission\Web\Responder\PageResponder;

class NotFound extends PageResponder {

	protected function respond() {
		$this->getResponse()->setStatusCode(404);
	}
	
}