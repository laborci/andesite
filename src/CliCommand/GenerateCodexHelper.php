<?php namespace Andesite\CliCommand;

use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateCodexHelper extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'ghost:codex', 'codex', "Generates codex ghost helpers" ) extends CliCommand{

			protected function configure(){
				$this->addArgument('name', InputArgument::OPTIONAL);
			}

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				CodexHelperGenerator::Service()->execute(new SymfonyStyle($input, $output), $this->config);
			}
		};
	}
}
