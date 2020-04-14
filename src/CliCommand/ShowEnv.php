<?php namespace Andesite\CliCommand;

use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\ConsoleTree;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowEnv extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'show:env', 'env', 'Generates and shows the current env' ) extends CliCommand{

			protected function configure(){
				$this->addArgument('key', InputArgument::OPTIONAL);
			}

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				Env::Service()->reload();
				if($input->getArgument('key')){
					$env = Env::Service()->get($input->getArgument('key'));
					$root = $input->getArgument('key');
				}else{
					$env = Env::Service()->get();
					$root = 'env';
				}
				$arr = array_filter($env, function ($key){ return strpos($key, '.') === false; }, ARRAY_FILTER_USE_KEY);
				ksort($arr);
				ConsoleTree::draw($arr, $style, $root);
			}
		};
	}

}

