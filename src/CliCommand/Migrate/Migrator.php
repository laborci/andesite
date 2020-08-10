<?php namespace Andesite\CliCommand\Migrate;

use Andesite\Core\Env\Env;
use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\DBAccess\Connection\PDOConnection;
use Andesite\DBAccess\ConnectionFactory;
use Application\Module\CliCommand\Diff;
use Rah\Danpu\Export;
use Symfony\Component\Console\Style\SymfonyStyle;
class Migrator{

	protected SymfonyStyle $style;
	public $location;
	protected PDOConnection $connection;
	protected $connectionName;

	public function __construct($connectionName, $location, SymfonyStyle $style){
		echo $connectionName;
		$this->style = $style;
		$this->connectionName = $connectionName;
		$this->connection = ConnectionFactory::Module()->get($this->connectionName);
		$this->location = realpath($location .'/'. $this->connectionName) . '/';
		if (!is_dir($this->location)) mkdir($this->location);
		chdir($this->location);
	}

	public function generate(bool $force){

		if (!$this->integrityCheck() || !$this->statusCheck()) return;

		$diff = $this->diffCheck();

		if ($diff['dirty'] === false && !$force){
			return;
		}
		$version = $this->getNextVersionNumber();
		$versionLocation = $this->location . $this->stringifyVersionNumber($version) . '/';
		if (!is_dir($versionLocation)) mkdir($versionLocation);

		foreach ($diff['views'] as $view => $migration){
			file_put_contents($versionLocation . 'up.view.' . $view . '.sql', $migration['up']);
			file_put_contents($versionLocation . 'down.view.' . $view . '.sql', $migration['down']);
		}
		foreach ($diff['tables'] as $table => $migration){
			file_put_contents($versionLocation . 'up.table.' . $table . '.sql', $migration['up']);
			file_put_contents($versionLocation . 'down.table.' . $table . '.sql', $migration['down']);
		}
		file_put_contents($versionLocation . 'up.script.sql', "SET FOREIGN_KEY_CHECKS = 0;\n--run up.table.*.sql\n--run up.view.*.sql\nSET FOREIGN_KEY_CHECKS = 1;\n");
		file_put_contents($versionLocation . 'down.script.sql', "SET FOREIGN_KEY_CHECKS = 0;\n--run down.table.*.sql\n--run down.view.*.sql\nSET FOREIGN_KEY_CHECKS = 1;\n");
		$this->connection->createSmartAccess()->insert('__migration', ['version' => $version, 'structure' => $this->getDump()]);
		$this->refresh($version);
		$this->style->writeln('<fg=green>Version ' . $version . ' generated.</>');
	}

	public function migrate($version, $force){
		if($version === 'latest') $version = $this->getLatestMigrationVersion();
		if (!$this->integrityCheck() && !$force) return;
		$this->style->writeln('<fg=black;bg=cyan;options=bold> Migrate database to version ' . $version . ' </>');
		$version = intval($version);
		$current = intval($this->getCurrentVersion());
		if (!is_dir($this->location . $this->stringifyVersionNumber($version))){
			$this->style->writeln('<fg=black;bg=red;options=bold> Migration ' . $version . ' not found!</>');
		}
		if ($version === $current){
			$this->style->writeln('Database is already on the requested version.');
		}elseif ($version < $current){
			$this->style->writeln('Downgrade from ' . $current . ' to ' . $version);
			$this->style->newLine();
			$migrations = $this->connection->createSmartAccess()->getRows("SELECT * FROM __migration WHERE version>" . $version . " ORDER BY version DESC");
			foreach ($migrations as $migration){
				$this->style->writeln('<fg=black;options=bold>Downgrade from version: ' . $migration['version'] . '</>');
				$this->style->writeln(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $migration['rollback']));
				$this->connection->exec($migration['rollback']);
				$this->connection->createSmartAccess()->delete('__migration', Filter::where('version=$1', $migration['version']));
			}
		}elseif ($version > $current){
			$this->style->writeln('Upgrade from ' . $current . ' to ' . $version);
			$this->style->newLine();
			for ($i = $current + 1; $i <= $version; $i++){
				$this->style->writeln('<fg=black;options=bold>Upgrade to version: ' . $i . '</>');
				$sql = $this->parseScript($this->location . $this->stringifyVersionNumber($i) . '/', 'up.script.sql');
				$this->style->writeln(preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $sql));
				$this->connection->exec($sql);
				$this->connection->createSmartAccess()->insert('__migration', ['version' => $i, 'structure' => $this->getDump()]);
				$this->refresh($i);
			}
		}
	}

	public function init(){
		$access = $this->connection->createSmartAccess();
		echo $this->location;
		if (!file_exists($this->location)) mkdir($this->location, 0777, true);
		if (!$access->tableExists('__migration')){
			$access->addTable('__migration', "(
					  `structure` text NOT NULL,
					  `rollback` text,
					  `version` int(11) unsigned NOT NULL,
					  `integrity` varchar(255) DEFAULT '',
					  UNIQUE KEY `version` (`version`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
		}
		$this->style->success('done');
	}

	public function refresh($version){
		if($version === 'current') $version = $this->getCurrentVersion();
		$this->style->writeln('Refreshing version ' . $version);
		$versionLocation = $this->location . $this->stringifyVersionNumber($version) . '/';
		$downScript = $this->parseScript($versionLocation, 'down.script.sql');
		$this->connection->createSmartAccess()->update('__migration', Filter::where('version=$1', $version), ['rollback' => $downScript, 'integrity' => $this->calculateIntegrity($version)]);
		$this->style->writeln('Done.');
	}

	public function integrityCheck(){
		$this->style->writeln('<fg=black;bg=cyan;options=bold> Integrity Check </>');
		$access = $this->connection->createSmartAccess();
		$versions = $access->getRows('SELECT version, integrity FROM __migration ORDER BY version');
		$lastClean = null;
		foreach ($versions as $version){
			$this->style->write('<fg=cyan>' . $this->stringifyVersionNumber($version['version']) . '</> ' . $version['integrity'] . ' ');
			$integrity = $this->calculateIntegrity($version['version']);
			if ($integrity === null){
				$this->style->writeln('<bg=red;fg=black> LOST  </>');
				if (is_null($lastClean)) $lastClean = intval($version['version']);
			}elseif ($version['integrity'] !== $integrity){
				$this->style->writeln('<bg=red;fg=black> DIRTY </>');
				if (is_null($lastClean)) $lastClean = intval($version['version']);
			}else{
				$this->style->writeln('<bg=green;fg=black> CLEAN </>');
			}
		}

		if (!is_null($lastClean)){
			$this->style->writeln('<fg=red>Rollback to version ' . ( $lastClean - 1 ) . ' (<fg=red;options=bold>andesite mig -mf ' . ( $lastClean - 1 ) . '</>) or refresh state (<fg=red;options=bold>andesite mig -r ' . ( $lastClean ) . '</>) to continue!</>');
		}
		return is_null($lastClean);
	}

	public function statusCheck(){
		$this->style->writeln('<bg=cyan;fg=black> Status check </>');
		$current = $this->getCurrentVersion();
		$latest = $this->getLatestMigrationVersion();
		if ($latest !== $current){
			$this->style->writeln('<fg=red>Your database (' . $current . ') is not on the latest migration (' . $latest . ') version !</>');
			return false;
		}else{
			$this->style->writeln('<fg=green>Ok, version: ' . $current . '</>');
			return true;
		}
	}

	public function diffCheck(){
		$this->style->writeln('<bg=cyan;fg=black> Diff check </>');
		$diff = $this->getDiff();
		if ($diff['dirty'] === false){
			$this->style->writeln('No changes found!');
		}else{
			$this->style->writeln('Changes:');
			foreach ($diff['tables'] as $table => $migration){
				$this->style->writeln('<fg=black>[' . $table . ']</>');
				$this->style->writeln($migration['up']);
			}
			foreach ($diff['views'] as $view => $migration){
				$this->style->writeln('<fg=black>[' . $view . ']</>');
				$this->style->writeln($migration['up']);
			}
		}
		return $diff;
	}

	protected function parseScript($location, $script){
		$script = file_get_contents($location . $script);
		$matches = preg_match_all("/^--run\s+(.*?)$/m", $script, $result);
		for ($i = 0; $i < $matches; $i++){
			$files = glob($location . $result[1][$i]);
			$includes = '';
			foreach ($files as $file){
				$includes .= file_get_contents($file) . "\n";
			}
			$script = str_replace($result[0][$i], $includes, $script);
		}
		return $script;
	}

	protected function getDump(){
		$dumper = ConnectionFactory::Module()->getDumper($this->connectionName);
		$tmp = Env::Service()->get('path.tmp') . 'dump.sql';
		$dumper->getDumper()->structure(true)->disableForeignKeyChecks(true)->data(false)->file($tmp);
		new Export($dumper->getDumper());
		$structure = file_get_contents($tmp);
		unlink($tmp);
		return $structure;
	}

	protected function getDiff(){
		$prevStructure = $this->getPreviousStructure();
		$structure = $this->getDump();
		return ( new Diff($prevStructure, $structure, $this->connection) )->getDiff();
	}

	protected function getCurrentVersion(){
		$access = $this->connection->createSmartAccess();
		return $access->getValue('SELECT Max(version) FROM __migration');
	}

	protected function getLatestMigrationVersion(){
		$dirs = glob('*', GLOB_ONLYDIR);
		return intval(end($dirs));
	}

	protected function getNextVersionNumber(){
		$dirs = glob('*', GLOB_ONLYDIR);
		if (count($dirs) === 0) return 1;
		return intval(end($dirs)) + 1;
	}

	protected function getPreviousStructure(){
		return $this->connection->createSmartAccess()->getValue("SELECT structure FROM __migration ORDER BY version DESC LIMIT 1");
	}

	protected function stringifyVersionNumber($version){ return str_pad($version, 6, '0', STR_PAD_LEFT); }

	protected function calculateIntegrity($version){
		if (!is_dir($this->stringifyVersionNumber($version))) return null;
		$files = glob($this->stringifyVersionNumber($version) . '/*.sql');
		$hash = '';
		foreach ($files as $file) $hash .= md5_file($file);
		return md5($hash);
	}

	
}