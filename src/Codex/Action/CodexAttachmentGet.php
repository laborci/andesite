<?php namespace Andesite\Codex\Action;


class CodexAttachmentGet extends Responder{
	
	protected function codexRespond(): ?array{
		$formHandler = $this->adminDescriptor->getFormHandler();
		try{
			$id = $this->getPathBag()->get('id');
			return $formHandler->getAttachments($id);
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return['message'=>$exception->getMessage()];
		}
	}

}

