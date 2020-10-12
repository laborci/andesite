<?php namespace Andesite\CliCommand;

use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DevMode extends CommandModule{

	/**
	 * @command       devmode
	 * @description   Sets devmode
	 */
	public function env(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				if(file_exists(getenv('root').'.devmode')){
					unlink(getenv('root').'.devmode');
					$this->style->success('dev mode turned OFF');
				}else{
					touch(getenv('root').'.devmode');
					$this->style->success('dev mode turned ON');
				}
			}
		} );
	}
}

