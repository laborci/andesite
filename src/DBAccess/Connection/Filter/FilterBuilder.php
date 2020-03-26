<?php namespace Andesite\DBAccess\Connection\Filter;

use Andesite\DBAccess\Connection\PDOConnection;

class FilterBuilder {

	protected $connection;

	public function __construct(PDOConnection $connection) { $this->connection = $connection; }

	public function getSql(array $where): ?string {
		if (!$where) return null;
		$sql = '';
		foreach ($where as $filterSegment) {
			if($filterSegment['sql'] instanceof Comparison){
				$filterSegment['sql'] = $filterSegment['sql']->getSql($this->connection);
			}elseif ($filterSegment['sql'] instanceof Filter)
				$filterSegment['sql'] = $filterSegment['sql']->getSql($this->connection);
			else if (is_array($filterSegment['sql']))
				$filterSegment['sql'] = $this->getSqlFromArray($filterSegment['sql']);
			if (trim($filterSegment['sql'])) {
				if ($sql) $sql .= " " . $filterSegment['type'] . " ";
				$sql .= "(" . $this->connection->applySQLParameters($filterSegment['sql'], $filterSegment['args']) . ")";
			}
		}
		return $sql;
	}

	protected function getSqlFromArray(array $filter): ?string {
		if (!$filter) return null;
		$sql = [];
		foreach ($filter as $key => $value) {
			$sql[] = is_array($value) ?
				$this->connection->applySQLParameters(" `" . $key . "` IN ($1) ", $value) :
				$this->connection->applySQLParameters(" `" . $key . "` = $1 ", $value);
		}
		return implode(' AND ', $sql);
	}


}