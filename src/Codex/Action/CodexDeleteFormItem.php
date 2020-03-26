<?php namespace Andesite\Codex\Action;

class CodexDeleteFormItem extends Responder{
	
	protected function codexRespond(): ?array{

		$formHandler = $this->adminDescriptor->getFormHandler();
		$id = $this->getPathBag()->get('id');
		try{
			$formHandler->delete($id);
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return[
				'message'=>$exception->getMessage()
			];
		}
		return [];
	}

}

