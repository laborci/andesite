<?php namespace Andesite\CliCommand\Migrate;

use Andesite\DBAccess\Connection\PDOConnection;
use Camcima\MySqlDiff\Parser;

class Diff{

	protected $diff;

	public function getDiff(){ return $this->diff; }

	public function __construct($from, $to, PDOConnection $connection){
		$parser = new Parser();
		$fromDb = $parser->parseDatabase($from);
		$access = $connection->createSmartAccess();

		$diffViews = [];

		$matches = preg_match_all('/^CREATE\s+(.*?)VIEW\s+`(.*?)`\s+AS\s+(.*?);$/m', $from, $result);
		for ($i = 0; $i < $matches; $i++) $diffViews[$result[2][$i]] = new ViewDiff($result[2][$i], $result[3][$i], '');

		$diffTables = [];
		$views = [];
		$tables = [];
		$dirty = false;

		foreach ($fromDb->getTables() as $table) if ($table->getName() !== '__migration') $diffTables[$table->getName()] = new TableDiff($table->getName(), $table->getCreationScript(), '');

		foreach ($access->getValues('Show Tables') as $table){
			if ($table !== '__migration'){
				if ($access->getTableType($table) === 'VIEW'){
					$create = $access->query("SHOW CREATE TABLE `" . $table . "`")->fetchColumn(1);
					preg_match('/^CREATE\s+(.*?)VIEW\s+`(.*?)`\s+AS\s+(.*?)$/', $create, $result);
					if (array_key_exists($table, $diffViews)) $diffViews[$table]->to = $result[3];
					else $diffViews[$table] = new ViewDiff($table, '', $result[3]);
				}else{
					$create = $access->query("SHOW CREATE TABLE `" . $table . "`")->fetchColumn(1) . ";";
					if (array_key_exists($table, $diffTables)) $diffTables[$table]->to = $create;
					else $diffTables[$table] = new TableDiff($table, '', $create);
				}
			}
		}

		foreach ($diffTables as $diff) if ($diff->up() !== null){
			$dirty = true;
			$tables[$diff->name] = [
				'up'   => $diff->up(),
				'down' => $diff->down(),
			];
		}

		foreach ($diffViews as $diff) if ($diff->up() !== null){
			$dirty = true;
			$views[$diff->name] = [
				'up'   => $diff->up(),
				'down' => $diff->down(),
			];
		}

		$this->diff = [
			'views'  => $views,
			'tables' => $tables,
			'dirty'  => $dirty,
		];
	}
}