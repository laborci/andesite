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


class MkDir extends CommandModule{
	/**
	 * @command       mkdir
	 * @alias         md
	 * @description   Creates all directories defined in ini
	 */
	public function env(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				Env::Service()->reload(true);
				$path = Env::Service()->get('path');
				$style = $this->$style;
				array_walk_recursive($path, function ($item, $key) use ($style){
					$item = rtrim($item, '/');
					if (!file_exists($item)){
						if (mkdir($item, 0777, true)) $style->success($item . 'created');
						else $style->error($item . ' could not be created');
					}elseif (!is_dir($item)){
						$style->warning($item . ' alread exists as a file');
					}else{
						$style->note($item . ' already exists');
					}
				});
			}
		} );
	}
}
