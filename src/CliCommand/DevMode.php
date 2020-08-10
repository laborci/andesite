<?php namespace Andesite\CliCommand;

use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DevMode extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'devmode', null, "sets devmode" ) extends CliCommand{

			protected function configure(){
			}

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				if(file_exists(getenv('root').'.devmode')){
					unlink(getenv('root').'.devmode');
					$style->success('dev mode turned OFF');
				}else{
					touch(getenv('root').'.devmode');
					$style->success('dev mode turned ON');
				}
			}
		};
	}
}
