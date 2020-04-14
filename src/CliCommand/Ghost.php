<?php namespace Andesite\CliCommand;

use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Andesite\GhostGenerator\GhostGenerator;

class Ghost extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, "generate:ghosts", 'ghost', 'Creates ghost entities') extends CliCommand{

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				GhostGenerator::Service()($style);
			}
		};
	}

}
