<?php namespace Andesite\CliCommand;

use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Andesite\Mission\Cli\ConsoleTree;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowEnv extends CommandModule{
	/**
	 * @command       env
	 * @description   Generates and shows the current env
	 */
	public function env(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				Env::Service()->reload(true);
				if ($this->input->getArgument('key')){
					$env = Env::Service()->get($this->input->getArgument('key'));
					$root = $this->input->getArgument('key');
				}else{
					$env = Env::Service()->get();
					$root = 'env';
				}
				if (is_array($env)){
					$arr = array_filter($env, function ($key){ return strpos($key, '.') === false; }, ARRAY_FILTER_USE_KEY);
					ksort($arr);
					ConsoleTree::draw($arr, $this->style, $root);
				}else{
					$this->style->success($env);
				}
			}
		} )->addArgument('key', InputArgument::OPTIONAL);
	}
}
