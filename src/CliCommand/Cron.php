<?php namespace Andesite\CliCommand;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Andesite\Util\Alert\AlertInterface;
use Andesite\Util\Cron\AbstractTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Cron extends CommandModule{

	/**
	 * @command run-task
	 * @alias cron
	 * @description Runs task
	 */
	public function cron(): Cmd{
		return ( new class extends Cmd{

			public function __invoke(){
				if ($this->input->getArgument('task')){
					$taskname = $this->input->getArgument('task');
					if (!array_key_exists($taskname, $this->config)) throw new \Exception('Cron task could not be found (' . $taskname . ')');
					$task = $this->config[$taskname];
					if (!is_array($task)){
						$task = ['class' => $task, 'config' => [],];
					}
					if ($task['class']) ( function ($class): AbstractTask{ return ServiceContainer::get($class); } )($task['class'])->run($task['config']);
				}else{
					$table = [];
					foreach ($this->config as $taskname => $class){
						if (is_array($class)) $class = $class['class'];
						$table[] = [$taskname, $class];
					}
					$this->style->table(['name', 'class'], $table);
				}
			}

		} )->addArgument('task', InputArgument::OPTIONAL, 'task to run');
	}

}
