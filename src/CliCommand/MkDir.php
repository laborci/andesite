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

class MkDir extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'mkdir', 'md', 'Creates all directories defined in ini' ) extends CliCommand{
			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				Env::Service()->reload(true);
				$path = Env::Service()->get('path');
				array_walk_recursive($path, function ($item, $key) use ($style){
					$item = rtrim($item, '/');
					if(!file_exists($item)){
						if(mkdir($item, 0777, true)) $style->success($item . 'created');
						else $style->error($item.' could not be created');
					}elseif(!is_dir($item)){
						$style->warning($item.' alread exists as a file');
					}else{
						$style->note($item.' already exists');
					}
				});
			}
		};
	}

}

