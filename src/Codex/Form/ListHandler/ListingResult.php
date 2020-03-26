<?php namespace Andesite\Codex\Form\ListHandler;

class ListingResult{

	public $rows = [];
	public $count;
	public $page;

	public function __construct($rows, $count, $page){
		$this->count = intval($count);
		$this->page = intval($page);
		$this->rows = $rows;
	}

}