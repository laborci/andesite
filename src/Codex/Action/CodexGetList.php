<?php namespace Andesite\Codex\Action;

class CodexGetList extends Responder{
	
	protected function codexRespond(): ?array{
		$page = $this->getPathBag()->get('page', 1);
		$sort = $this->getJsonParamBag()->get('sort');
		$listHandler = $this->adminDescriptor->getListHandler();
		$result = $listHandler->get($page, $sort);

		return [
			'rows'  => $result->rows,
			'count' => $result->count,
			'page'  => $result->page,
		];
	}

}

