<?php namespace Andesite\Mission\Web\Action;

use Andesite\Mission\Web\Responder\PageResponder;

class Forbidden extends PageResponder {

	protected function respond() {
		$this->getResponse()->setStatusCode(403);
	}
	
}