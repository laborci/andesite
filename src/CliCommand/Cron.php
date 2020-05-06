<?php namespace Andesite\CliCommand;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Util\Alert\AlertInterface;
use Andesite\Util\Cron\AbstractTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Cron extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'cron:task', 'cron', "Runs cron task" ) extends CliCommand{

			protected function configure(){
				$this->addOption('run', 'r', InputOption::VALUE_REQUIRED);
			}
			
			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				if ($input->getOption('run')){
					$taskname = $input->getOption('run');
					if (!array_key_exists($taskname, $config)) throw new \Exception('Cron task could not be found (' . $taskname . ')');

					$task = $config[$taskname];
					if (!is_array($task)){
						$task = [
							'class'  => $task,
							'config' => [],
						];
					}
					if($task['class']) ( function ($class): AbstractTask{ return ServiceContainer::get($class); } )($task['class'])->run($task['config']);
				}else{
					$table = [];
					foreach ($config as $taskname => $class){
						if (is_array($class)) $class = $class['class'];
						$table[] = [$taskname, $class];
					}
					$style->table(['name', 'class'], $table);
				}
			}

		};
	}

}
