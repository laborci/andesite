<?php namespace Andesite\CliCommand;

use Andesite\DBAccess\ConnectionFactory;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DumpDB extends CliModule{

	protected function createCommand($config): Command{

		return new class( $config, 'generate:dump', 'dump', "Creates database dumps" ) extends CliCommand{

			protected function configure(){
				$this
					->addOption("structure", "s", InputOption::VALUE_NONE, "Dump structure")
					->addOption("data", "d", InputOption::VALUE_NONE, "Dump data")
					->addOption("database", "db", InputOption::VALUE_REQUIRED, "Database name", "default");
			}

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
			
				$dumper = ConnectionFactory::Module()->getDumper($input->getOption('database'));

				if ($input->getOption('structure') === false && $input->getOption('data') === false){
					$file =  'snapshot.' . $input->getOption('database') . '.' . date('Y-m-d.H-i-s') . '.sql';
					$style->title('dumping snapshot: ' . $file);
					$dumper->dump($file);
					$style->success('done');
				}

				if ($input->getOption('structure') !== false){
					$file =  'structure.' . $input->getOption('database') . '.' . date('Y-m-d.H-i-s') . '.sql';
					$style->title('dumping structure: ' . $file);
					$dumper->structure($file);
					$style->success('done');
				}

				if ($input->getOption('data') !== false){
					$file =  'data.' . $input->getOption('database') . '.' . date('Y-m-d.H-i-s') . '.sql';
					$style->title('dumping data' . $file);
					$dumper->data($file);
					$style->success('done');
				}
			}
		};
	}

}
