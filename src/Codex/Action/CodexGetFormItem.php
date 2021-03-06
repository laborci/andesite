<?php namespace Andesite\Codex\Action;

class CodexGetFormItem extends Responder{
	
	protected function codexRespond(): ?array{

		$formHandler = $this->adminDescriptor->getFormHandler();
		$id = $this->getPathBag()->get('id');
		try{
			if($id === 'new'){
				$item = $formHandler->getNew();
			}else{
				$item = $formHandler->get($id);
			}
			if(is_null($item))throw new \Exception("Item not found!");
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return[
				'message'=>$exception->getMessage()
			];
		}

		return [
			'descriptor'=>$formHandler,
			'data'=>$item
		];
	}

}

