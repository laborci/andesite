<?php namespace Andesite\DBAccess\Connection;

use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\Util\Memcache\Memcache;


class Finder{

	/** @var PDOConnection */
	protected $connection;
	/** @var callable */
	protected $converter;
	protected $select;
	/** @var Filter */
	protected $filter;
	protected $from;
	protected $limit;
	protected $offset;
	protected $order = [];

	private $cache = false;

	public function __construct(PDOConnection $connection){
		$this->connection = $connection;
	}

	public function cache($sec){
		$this->cache = $sec;
		return $this;
	}

	/**
	 * @param callable|null $converter
	 * @return $this
	 */
	public function setConverter(callable $converter = null){
		$this->converter = $converter;
		return $this;
	}

	/**
	 * @param string $sql
	 * @param array  $sqlParams
	 * @return $this
	 */
	public function select(string $sql, ...$sqlParams){
		$this->select = $this->connection->applySQLParameters($sql, $sqlParams);
		return $this;
	}

	/**
	 * @param string $sql
	 * @param array  $sqlParams
	 * @return $this
	 */
	public function from(string $sql, ...$sqlParams){
		$this->from = $this->connection->applySQLParameters($sql, $sqlParams);
		return $this;
	}

	/**
	 * @param Filter|null $filter
	 * @return $this
	 */
	public function where(Filter $filter = null){
		$this->filter = $filter;
		return $this;
	}

	#region ORDER
	/**
	 * @param $order
	 * @return $this
	 */
	public function order($order){
		if (is_array($order)) foreach ($order as $field => $dir) $this->order[] = $this->connection->escapeSQLEntity($field) . ' ' . $dir;
		else $this->order[] = $order;
		return $this;
	}

	/**
	 * @param $field
	 * @return $this
	 */
	public function asc($field){ return $this->order($this->connection->escapeSQLEntity($field) . ' ASC'); }

	/**
	 * @param $field
	 * @return $this
	 */
	public function desc($field){ return $this->order($this->connection->escapeSQLEntity($field) . ' DESC'); }

	/**
	 * @param bool   $cond
	 * @param string $field
	 * @return $this
	 */
	public function ascIf(bool $cond, string $field){ return $cond ? $this->asc($field) : $this; }

	/**
	 * @param bool   $cond
	 * @param string $field
	 * @return $this
	 */
	public function descIf(bool $cond, string $field){ return $cond ? $this->desc($field) : $this; }

	/**
	 * @param bool $cond
	 * @param      $order
	 * @return $this
	 */
	public function orderIf(bool $cond, $order){ return $cond ? $this->order($order) : $this; }
	#endregion

	public function collect($limit = null, $offset = null): array{
		$records = $this->collectRecords($limit, $offset);
		$records = $this->convertRecords($records);
		return $records;
	}

	public function pick(){ return $this->convertRecord($this->pickRecord()); }

	public function collectPage($pageSize, $page, &$count = 0): array{
		$records = $this->collectPageRecords($pageSize, $page, $count);
		$records = $this->convertRecords($records);
		return $records;
	}

	protected function pickRecord(){
		$records = $this->collectRecords(1, null);
		if ($records){
			return array_shift($records);
		}else return null;
	}

	protected function collectPageRecords($pageSize, $page, &$count = 0): array{
		$pageSize = abs(intval($pageSize));
		$page = abs(intval($page));
		return $this->collectRecords($pageSize, $pageSize * ( $page - 1 ), $count);
	}

	protected function convertRecord($record){
		$converter = $this->converter;
		return is_null($converter) || is_null($record) ? $record : $converter($record);
	}

	protected function convertRecords($records){
		if (!is_null($this->converter)){
			foreach ($records as $key => $record){
				$converter = $this->converter;
				$records[$key] = $converter($record);
			}
		}
		return $records;
	}

	protected function collectRecords($limit = null, $offset = null, &$count = false): array{
		$doCounting = !is_null($limit) && $count !== false;
		$this->limit = $limit;
		$this->offset = $offset;
		$sql = $this->buildSQL($doCounting);

		if ($this->cache && !$doCounting){
			$records = Memcache::Module()->get('finder/' . md5($sql));
			if (is_array($records)) return $records;
		}

		$pdostatement = $this->connection->query($sql);
		$records = $pdostatement->fetchAll($this->connection::FETCH_ASSOC);
		if ($this->cache && !$doCounting){
			Memcache::Module()->set('finder/' . md5($sql), $records, $this->cache);
		}


		if ($doCounting){
			$pdostatement = $this->connection->query('SELECT FOUND_ROWS()');
			$count = $pdostatement->fetch($this->connection::FETCH_COLUMN);
		}

		return $records;
	}

	public function count(): int{
		$sql = /** @lang MySQL */
			'SELECT Count(1) FROM ' . $this->from . ' ' . ( $this->filter != null ? ' WHERE ' . $this->filter->getSql($this->connection) . ' ' : '' );
		$pdostatement = $this->connection->query($sql);
		return $pdostatement->fetch($this->connection::FETCH_COLUMN);
	}

	public function buildSQL($doCounting = false): string{
		return
			'SELECT ' .
			( $doCounting ? 'SQL_CALC_FOUND_ROWS ' : '' ) .
			$this->select . ' ' .
			' FROM ' . $this->from . ' ' .
			( $this->filter != null ? ' WHERE ' . $this->filter->getSql($this->connection) . ' ' : '' ) .
			( count($this->order) ? ' ORDER BY ' . join(', ', $this->order) : '' ) .
			( $this->limit ? ' LIMIT ' . $this->limit : '' ) .
			( $this->offset ? ' OFFSET ' . $this->offset : '' );
	}

	public function fetch($fetchmode = PDOConnection::FETCH_ASSOC): array{
		return $this->connection->query($this->buildSQL())->fetch($fetchmode);
	}

	public function fetchAll($fetchmode = PDOConnection::FETCH_ASSOC): array{
		return $this->connection->query($this->buildSQL())->fetchAll($fetchmode);
	}
}
