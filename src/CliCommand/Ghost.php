<?php namespace Andesite\CliCommand;

use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Andesite\GhostGenerator\GhostGenerator;

class Ghost extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config ) extends Command{

			private $config;

			public function __construct($config){
				parent::__construct();
				$this->config = $config;
			}

			protected function configure(){
				$this
					->setName('ghost')
					->setDescription('Creates ghost entities');
			}

			protected function execute(InputInterface $input, OutputInterface $output){
				GhostGenerator::Service()(new SymfonyStyle($input, $output));
			}
		};
	}

}
