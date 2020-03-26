<?php namespace Andesite\Codex\Action;

class CodexSaveFormItem extends Responder{
	
	protected function codexRespond(): ?array{

		$formHandler = $this->adminDescriptor->getFormHandler();
		try{
			$id = $formHandler->save($this->getJsonParamBag()->get('id'), $this->getJsonParamBag()->get('fields'));
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return[
				'message'=>$exception->getMessage()
			];
		}
		return ['id'=>$id];

	}

}

