<?php namespace Andesite\DBAccess\Connection;

use Andesite\DBAccess\Connection\Filter\FilterBuilder;

class PDOConnection extends \PDO{

	private $dsn;
	private $username;
	private $passwd;
	private $options;

	public function getDsn(){ return $this->dsn; }
	public function getUsername(){ return $this->username; }
	public function getPasswd(){ return $this->passwd; }
	public function getOptions(){ return $this->options; }

	public function __construct($dsn, $username = null, $passwd = null, $options = null){
		$this->dsn = $dsn;
		$this->username = $username;
		$this->passwd = $passwd;
		$this->options = $options;
		parent::__construct($dsn, $username, $passwd, $options);
	}

	/** @var SqlLogHookInterface */
	protected $sqlLogHook;
	public function setSqlLogHook($hook){ $this->sqlLogHook = $hook; }

	public function query($statement, $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = []){
		if (!is_null($this->sqlLogHook)) $this->sqlLogHook->log($statement);
		return parent::query($statement);
	}

	public function quoteValue($subject, bool $addQuotationMarks = true): string{ return $subject === null ? 'NULL' : ( $addQuotationMarks ? $this->quote($subject) : trim($this->quote($subject), "'") ); }
	public function quoteArray(array $array, bool $addQuotationMarks = true): array{ return array_map(function ($val) use ($addQuotationMarks){ return $this->quote($val, $addQuotationMarks); }, $array); }
	public function escapeSQLEntity($subject): string{ return '`' . $subject . '`'; }
	public function escapeSQLEntities(array $array): array{ return array_map(function ($val){ return $this->escapeSQLEntity($val); }, $array); }
	public function applySQLParameters(string $sql, array $sqlParams = []): string{
		if (count($sqlParams)){
			foreach ($sqlParams as $key => $param){
				$valueParam = is_array($param) ? join(',', $this->quoteArray($param)) : $this->quote($param);
				$sql = str_replace('$' . ( $key + 1 ), $valueParam, $sql);
				if (!is_array($param)){
					$sqlEntityParam = $this->escapeSQLEntity($param);
					$sql = str_replace('@' . ( $key + 1 ), $sqlEntityParam, $sql);
				}
			}
		}
		return $sql;
	}


	public function createFinder(): Finder{ return new Finder($this); }

	private $smartAccess;
	public function createSmartAccess(): SmartAccess{ return $this->smartAccess ? $this->smartAccess : ( $this->smartAccess = new SmartAccess($this) ); }

	private $repositories = [];
	public function createRepository(string $table): Repository{ return array_key_exists($table, $this->repositories) ? $this->repositories[$table] : ( $this->repositories[$table] = new Repository($this, $table) ); }

	private $filterBuilder;
	public function createFilterBuilder(): FilterBuilder{ return $this->filterBuilder ? $this->filterBuilder : ( $this->filterBuilder = new FilterBuilder($this) ); }

}

