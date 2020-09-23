<?php namespace Andesite\Codex\Action;


class CodexAttachmentRename extends Responder{
	
	protected function codexRespond(): ?array{
		$formHandler = $this->adminDescriptor->getFormHandler();

		try{
			$id = $this->getPathBag()->get('id');
			$file = $this->getJsonParamBag()->get('filename');
			$category = $this->getJsonParamBag()->get('category');
			$newname = $this->getJsonParamBag()->get('newname');
			return $formHandler->renameAttachment($id, $file, $category, $newname);
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return['message'=>$exception->getMessage()];
		}
	}

}

