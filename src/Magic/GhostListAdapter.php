<?php namespace Andesite\Magic;

use Andesite\DBAccess\Connection\Filter\Comparison;
use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\Ghost\Ghost;


class GhostListAdapter implements ListAdapterInterface{

	/** @var \Andesite\Ghost\Ghost */
	private $ghost;
	/** @var callable */
	private $quickSearchFilterCreator;
	/** @var callable */
	private $searchFilterCreator;
	/** @var callable */
	private $baseFilterCreator;
	/** @var array|string[] */
	private array $quickSearchFields;
	/** @var callable */
	private $export;

	public function __construct($ghost, array $quickSearchFields = ['id']){
		$this->ghost = $ghost;
		$this->quickSearchFields = $quickSearchFields;
	}

	/** @return $this */
	public function setBaseFilterCreator(callable $func){
		$this->baseFilterCreator = $func;
		return $this;
	}
	/** @return $this */
	public function setSearchFilterCreator(callable $func){
		$this->searchFilterCreator = $func;
		return $this;
	}
	/** @return $this */
	public function setQuickSearchFilterCreator(callable $func){
		$this->quickSearchFilterCreator = $func;
		return $this;
	}
	/** @return $this */
	public function setExport(callable $func){
		$this->export = $func;
		return $this;
	}

	/**
	 * @param $quickSearch
	 * @param $search
	 * @param $sort
	 * @param $offset
	 * @param $limit
	 * @return array['page'=>int, 'count' => int, 'items' => array]
	 */
	public function get($quickSearch, $search, $sort, $page, $pageSize): array{
		/** @var Filter $baseFilter */
		$baseFilter = is_null($this->baseFilterCreator) ? Filter::where("1=1") : ($this->baseFilterCreator)();
		$filter = $baseFilter->and($this->quickSearch($quickSearch))->and($this->search($search));
		$ghost = $this->ghost;
		$query = $ghost::search($filter);
		if(is_array($sort)) foreach ($sort as $field=>$direction){
			$query->descIf($direction === 'desc', $field);
			$query->ascIf($direction === 'asc', $field);
		}
		$items = $query->collectPage($pageSize, $page, $count);
		if (count($items) === 0 && $count > 0){
			$page = ceil($count / $pageSize);
			$items = $ghost::search($filter)->collectPage($pageSize, $page, $count);
		}
		$items = array_map(function (Ghost $item){
			return [
				'id'   => $item->id,
				'data' => !is_null($this->export) ? ( $this->export )($item) : $item->export($item),
			];
		}, $items);
		return [
			'page'  => $page,
			'count' => $count,
			'items' => $items,
		];
	}

	protected function quickSearch($search): ?Filter{
		if (is_null($search) || $search === '') return null;
		if (!is_null($this->quickSearchFilterCreator)) return ( $this->quickSearchFilterCreator )($search);
		elseif (count($this->quickSearchFields)){
			$filter = Filter::where(null);
			foreach ($this->quickSearchFields as $field){
				$filter->or(( new Comparison($field) )->instring($search));
			}
			return $filter;
		}
		return null;
	}

	protected function search($search): ?Filter{
		if (is_null($search)) return null;
		if (!is_null($this->searchFilterCreator)) return ( $this->searchFilterCreator )($search);
		if (count($search)){
			$filter = Filter::where(null);
			foreach ($search as $searchItem){
				$filter->and("`" . $searchItem['name'] . "` = $1", $searchItem['value']);
			}
			return $filter;
		}else return null;
	}

}