<?php namespace Andesite\Codex\Action;


class CodexAttachmentCrop extends Responder{
	
	protected function codexRespond(): ?array{
		$formHandler = $this->adminDescriptor->getFormHandler();

		try{
			$id = $this->getPathBag()->get('id');
			$file = $this->getJsonParamBag()->get('filename');
			$category = $this->getJsonParamBag()->get('category');
			$data = $this->getJsonParamBag()->get('data');
			return $formHandler->cropAttachment($id, $file, $category, $data);
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return['message'=>$exception->getMessage()];
		}
	}

}

