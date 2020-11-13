<?php namespace Andesite\Magic;

trait AttachmentRequestHandler{
	/** @accepts post */
	public function attachment($action){ return $this->createAttachmentAdapter()->handle($action, $this->getRequest(), $this->getResponse()); }
}