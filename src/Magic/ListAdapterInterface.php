<?php namespace Andesite\Magic;


interface ListAdapterInterface{
	/**
	 * @param $quickSearch
	 * @param $search
	 * @param $sort
	 * @param $offset
	 * @param $limit
	 * @return array{'count':int, 'items': array}
	 */
	public function get($quickSearch, $search, $sort, $offset, $limit);
}