<?php namespace Andesite\CliCommand;

use Andesite\CliCommand\Migrate\Module;
use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Ghost\GhostManager;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Andesite\GhostGenerator\GhostGenerator;

/**
 * @command-group ghost
 */
class Ghost extends CommandModule{
	/**
	 * @alias       ghost
	 * @description Generates ghosts
	 */
	public function generate(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){ GhostGenerator::Service()($this->style, $this->config); }
		});
	}

	/**
	 * @alias       codex
	 * @description Generates ghosts codex helpers
	 */
	public function codex(): Cmd{
		return (new class extends Cmd{
			public function __invoke(){
				CodexHelperGenerator::Service()->execute($this->style, $this->config['codexhelper']);
			}
		})->addArgument('name', InputArgument::OPTIONAL);;
	}

	/**
	 * @description Generates frontend ghosts
	 */
	public function js(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$this->style->note('Not implemented...');
			}
		});
	}
}
