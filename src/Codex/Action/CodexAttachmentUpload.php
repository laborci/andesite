<?php namespace Andesite\Codex\Action;

class CodexAttachmentUpload extends Responder{
	
	protected function codexRespond(): ?array{
		$formHandler = $this->adminDescriptor->getFormHandler();
		try{
			$id = $this->getPathBag()->get('id');
			$category = $this->getRequestBag()->get('category');
			/** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
			$file = $this->getFileBag()->get('file');
			$formHandler->uploadAttachment($id, $category, $file);
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return[
				'exception'=>get_class($exception),
				'message'=>$exception->getMessage()
			];
		}
		return [];
	}

}

