<?php namespace Andesite\Codex\Action;


class CodexAttachmentDelete extends Responder{
	
	protected function codexRespond(): ?array{
		try{
			$formHandler = $this->adminDescriptor->getFormHandler();
			$id = $this->getPathBag()->get('id');
			$file = $this->getJsonParamBag()->get('filename');
			$category = $this->getJsonParamBag()->get('category');
			$formHandler->deleteAttachment($id, $file, $category);
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return['message'=>$exception->getMessage()];
		}
		return [];
	}

}

