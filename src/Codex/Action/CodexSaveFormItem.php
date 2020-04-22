<?php namespace Andesite\Codex\Action;

use Andesite\Ghost\Exception\ValidationError;
class CodexSaveFormItem extends Responder{

	protected function codexRespond(): ?array{

		$formHandler = $this->adminDescriptor->getFormHandler();
		try{
			$id = $formHandler->save($this->getJsonParamBag()->get('id'), $this->getJsonParamBag()->get('fields'));
		}catch (ValidationError $error){
			$this->getResponse()->setStatusCode(400);
			return [
				'error'   => 'validation',
				'message' => $error->getMessages(),
			];
		}catch (\PDOException $exception){
			$this->getResponse()->setStatusCode(400);
			if ($exception->errorInfo[0] == 23000 && $exception->errorInfo[1] == 1062){
				preg_match("(.*?for key \'(.*?)\'$)", $exception->errorInfo[2], $matches);
				return ['error' => 'validation', 'message' => [['field'=>$matches[1], 'message'=>$matches[0]]]];
			}else{
				return ['error'=>'unknown', 'message'=>$exception->getMessage()];
			}
		}catch (\Throwable $exception){
			$this->getResponse()->setStatusCode(400);
			return ['error'=>'unknown', 'message' => $exception->getMessage()];
		}
		return ['id' => $id];

	}

}

