<?php namespace Andesite\CliCommand\Migrate;

use Camcima\MySqlDiff\Differ;
use Camcima\MySqlDiff\Model\DatabaseDiff;
use Camcima\MySqlDiff\Parser;
class TableDiff{

	public $name;
	public $from;
	public $to;

	public function __construct($name, $from, $to){
		$this->name = $name;
		$this->from = $from;
		$this->to = $to;
	}

	public function up(){
		$differ = new Differ();
		$parser = new Parser();
		$diff = $differ->diffDatabases($parser->parseDatabase($this->from), $parser->parseDatabase($this->to));
		return $this->createAlterScript($diff);
	}

	public function down(){
		$differ = new Differ();
		$parser = new Parser();
		$diff = $differ->diffDatabases($parser->parseDatabase($this->to), $parser->parseDatabase($this->from));
		return $this->createAlterScript($diff);
	}

	protected function createAlterScript(DatabaseDiff $diff){
		if (count($diff->getDeletedTables())) return "DROP TABLE IF EXISTS `" . $diff->getDeletedTables()[0]->getName() . "`;";
		if (count($diff->getNewTables())) return $diff->getNewTables()[0]->getCreationScript();
		if (count($diff->getChangedTables())) return $diff->getChangedTables()[0]->generateAlterScript();
		return null;
	}

}