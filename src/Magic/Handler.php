<?php namespace Andesite\Magic;

use Andesite\Ghost\Exception\ValidationError;
use Andesite\Mission\Web\Responder\ApiJsonResponder;

abstract class Handler extends ApiJsonResponder{

	abstract protected function createListAdapter(): ListAdapterInterface;
	abstract protected function createFormAdapter(): FormAdapterInterface;

	/** @accepts post */
	public function save($id = ''){
		try{
			$id = $this->createFormAdapter()->save($id, $this->getJsonParamBag()->all());
			return ['id' => $id];
		}catch (ValidationError $e){
			$this->getResponse()->setStatusCode(400);
			return $e->getMessages();
		}
	}

	/** @accepts get */
	public function get($id = ''){ return $this->createFormAdapter()->get($id); }

	/** @accepts delete */
	public function delete($id){ return $this->createFormAdapter()->delete($id); }

	/** @accepts post */
	public function list($page = 1){
		$quickSearch = trim($this->getJsonParamBag()->get('quickSearch', null));
		$search = $this->getJsonParamBag()->get('search', null);
		$sort = $this->getJsonParamBag()->get('sort', null);
		$pageSize = $this->getJsonParamBag()->get('limit');
		return $this->createListAdapter()->get($quickSearch, $search, $sort, $page, $pageSize);
	}

}
