<?php namespace Andesite\Magic;

use Andesite\Mission\Web\Responder\ApiJsonResponder;


class AttachmentRequest{

	private $ghost;
	private $params;
	private $files;

	public function __construct($ghost, $params, $files){
		$this->ghost = $ghost;
		$this->params = $params;
		$this->files = $files;
	}

	public function __invoke(){

	}

}