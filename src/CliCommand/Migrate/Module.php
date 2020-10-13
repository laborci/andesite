<?php namespace Andesite\CliCommand\Migrate;

use Andesite\DBAccess\ConnectionFactory;
use Andesite\Mission\Cli\CliCommandModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Camcima\MySqlDiff\RegExpPattern;
use Minime\Annotations\Reader;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * @command-group mig
 */
class Module extends CommandModule{

	public function __construct(Reader $reader){
		parent::__construct($reader);
		$ref = new \ReflectionClass(RegExpPattern::class);
		$regex = $ref->getStaticPropertyValue('columnTypeRegExps');
		$regex[] = 'json';
		$ref->setStaticPropertyValue('columnTypeRegExps', $regex);
	}

	/**
	 * @description Check the migration status
	 */
	public function status(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$migrator = new Migrator($this->input->getArgument('database'), $this->config, $this->style);
				$this->style->writeln('Database: <fg=black>'.$this->input->getArgument('database').'</>');
				$this->style->writeln('Migrations: <fg=black>'.$migrator->location.'</>');
				$migrator->integrityCheck();
				$migrator->statusCheck();
				$migrator->diffCheck();
			}
		})
			->addArgument('database', InputArgument::OPTIONAL, 'name of the database to work with', $this->config['default-database']);
	}

	/**
	 * @description Refreshes the integrity of a migration
	 */
	public function refresh(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$migrator = new Migrator($this->input->getArgument('database'), $this->config, $this->style);
				$version = $this->input->getArgument('version');
				$migrator->refresh($version);
			}
		})
			->addArgument('version', InputArgument::OPTIONAL, 'version of the migration to work with', 'current')
			->addArgument('database', InputArgument::OPTIONAL, 'name of the database to work with', $this->config['default-database']);
	}

	/**
	 * @description Initializes the migrations
	 */
	public function init(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$migrator = new Migrator($this->input->getArgument('database'), $this->config, $this->style);
				$migrator->init();
			}
		})
			->addArgument('database', InputArgument::OPTIONAL, 'name of the database to work with', $this->config['default-database']);
	}

	/**
	 * @description Migrate
	 */
	public function go(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$migrator = new Migrator($this->input->getArgument('database'), $this->config, $this->style);
				$migrator->migrate($this->input->getArgument('version'), $this->input->getOption('force'));
			}
		})
			->addOption('force', ['f'], null, 'Forces the migration generation, even if no changes found!')
			->addArgument('version', InputArgument::OPTIONAL, '', 'latest')
			->addArgument('database', InputArgument::OPTIONAL, 'name of the database to work with', $this->config['default-database']);
	}

	/**
	 * @description Generates new migration scripts
	 */
	public function generate(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$migrator = new Migrator($this->input->getArgument('database'), $this->config, $this->style);
				$migrator->generate($this->input->getOption('force'));
			}
		})
			->addOption('force', ['f'], null, 'Forces the migration generation, even if no changes found!')
			->addArgument('database', InputArgument::OPTIONAL, 'name of the database to work with', $this->config['default-database']);
	}

	/**
	 * @description Generates database dumps
	 * @alias dump
	 */
	public function dump(): Cmd{

		return ( new class extends Cmd{
			public function __invoke(){
				$style = $this->style;
				$input = $this->input;

				$dumper = ConnectionFactory::Module()->getDumper($input->getArgument('database'), $this->config['dump']['path']);

				if ($input->getOption('structure') === false && $input->getOption('data') === false){
					$file = 'snapshot.' . $input->getArgument('database') . '.' . date('Y-m-d.H-i-s') . '.sql';
					$style->title('dumping snapshot: ' . $file);
					$dumper->dump($file);
					$style->success('done');
				}

				if ($input->getOption('structure') !== false){
					$file = 'structure.' . $input->getArgument('database') . '.' . date('Y-m-d.H-i-s') . '.sql';
					$style->title('dumping structure: ' . $file);
					$dumper->structure($file);
					$style->success('done');
				}

				if ($input->getOption('data') !== false){
					$file = 'data.' . $input->getArgument('database') . '.' . date('Y-m-d.H-i-s') . '.sql';
					$style->title('dumping data' . $file);
					$dumper->data($file);
					$style->success('done');
				}
			}
		})
			->addOption("structure", "s", InputOption::VALUE_NONE, "Dump structure")
			->addOption("data", "d", InputOption::VALUE_NONE, "Dump data")
			->addArgument("database", InputArgument::OPTIONAL, "Database name", $this->config['default-database']);
	}
}


