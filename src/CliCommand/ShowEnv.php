<?php namespace Andesite\CliCommand;

use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliModule;
use Andesite\Util\DotArray\DotArray;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowEnv extends CliModule {

	protected function createCommand($config): Command{
		return new class($config) extends Command{

			private $config;

			public function __construct($config){
				parent::__construct();
				$this->config = $config;
			}

			protected function configure() {
				$this
					->setName('show-env')
					->setAliases(['showenv', 'se'])
					->setDescription('Creates ghost entities')
				;
			}

			protected function execute(InputInterface $input, OutputInterface $output) {
				$arr = Env::Service()->get();
				$arr = DotArray::flatten($arr);
				ksort($arr);

				foreach ($arr as $key=>$value){
					if(!is_array($value)) $env[] = [$key, $value];
				}
				$table = new Table($output);
				$table
					->setHeaders(['key', 'value'])
					->setRows($env)
				;
				$table->render();
			}
		};
	}



}
