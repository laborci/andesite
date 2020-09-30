<?php namespace Andesite\DBAccess\Connection;

use Andesite\DBAccess\Connection\Filter\Filter;


class Repository{

	/** @var string */
	protected $table;
	protected $escTable;
	/** @var PDOConnection */
	protected $connection;

	public function __construct($connection, $table){
		$this->table = $table;
		$this->connection = $connection;
		$this->escTable = $this->escapeSQLEntity($table);
	}

	public function search(Filter $filter = null): Finder{ return $this->connection->createFinder()->select($this->escTable . '.*')->from($this->escTable)->where($filter); }
	public function pick(int $id){ return $this->search(Filter::where('id = $1', $id))->pick(); }
	public function collect(array $ids){ return $this->search(Filter::where('id IN ($1)', $ids))->collect(); }
	public function count(Filter $filter = null){ return $this->connection->createFinder()->from($this->escTable)->where($filter)->count(); }
	public function save($record){ return $record['id'] ? $this->update($record) : $this->insert($record); }

	public function getTable(): string{ return $this->table; }
	public function getConnection(): \Andesite\DBAccess\Connection\PDOConnection{ return $this->connection; }

	protected function quoteValue($value){ return $this->connection->quoteValue($value); }
	protected function escapeSQLEntity($value){ return $this->connection->escapeSQLEntity($value); }
	protected function query($sql){ return $this->connection->query($sql); }

	public function insert(array $record, $insertIgnore = false){
		$data = [];
		foreach ($record as $key => $value) if ($key != 'id'){
			if (substr($key, 0, 1) === '!'){
				$key = substr($key, 1);
			}else{
				$value = $this->quoteValue($value);
			}
			$data[] = [$this->escapeSQLEntity($key), $value];
		}
		$sql = 'INSERT ' . ( $insertIgnore ? 'IGNORE' : '' ) . ' INTO ' . $this->escTable .
			' (' . join(', ', array_column($data, 0)) . ') ' .
			' VALUE(' . join(', ', array_column($data, 1)) . ')';
		$this->query($sql);
		return $this->connection->lastInsertId();
	}

	public function update($record, $id=null): int{
		$data = [];
		if(is_null($id) && !array_key_exists('id', $record)) return false;
		if(is_null($id)) $id = $record['id'];
		unset($record['id']);
		foreach ($record as $key => $value){
			if (substr($key, 0, 1) === '!'){
				$key = substr($key, 1);
			}else{
				$value = $this->quoteValue($value);
			}
			$data[] = $this->escapeSQLEntity($key) . '=' . $value;
		}
		$sql = 'UPDATE ' . $this->escTable . ' SET ' . implode(', ', $data) . ' WHERE id=' . $this->quoteValue($id);
		return $this->query($sql)->rowCount();
	}

	public function delete(int $id){ return $this->query("DELETE FROM " . $this->escTable . " WHERE id = " . $this->quoteValue($id))->rowCount(); }
}
