<?php namespace Andesite\Codex\Action;


class CodexAttachmentReorder extends Responder{
	
	protected function codexRespond(): ?array{
		$formHandler = $this->adminDescriptor->getFormHandler();

		try{
			$id = $this->getPathBag()->get('id');
			$file = $this->getJsonParamBag()->get('filename');
			$category = $this->getJsonParamBag()->get('category');
			$sequence = $this->getJsonParamBag()->get('sequence');
			return $formHandler->reorderAttachment($id, $file, $category, $sequence);
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return['message'=>$exception->getMessage()];
		}
	}

}

