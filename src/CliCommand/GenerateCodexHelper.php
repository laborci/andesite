<?php namespace Andesite\CliCommand;

use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateCodexHelper extends CliModule {


	protected function createCommand($config): Command{
		return new class($config) extends Command{
			/** @var SymfonyStyle */
			protected $output;
			private $config;

			public function __construct($config){
				parent::__construct('generate-codex-helper');
				$this->config = $config;
			}

			protected function configure() {
				$this
					->setAliases(['gch'])
					->addArgument('name', InputArgument::OPTIONAL)
				;
			}

			protected function execute(InputInterface $input, OutputInterface $output) 	{
				CodexHelperGenerator::Service()->execute(new SymfonyStyle($input, $output), $this->config);
			}
		};
	}
}
