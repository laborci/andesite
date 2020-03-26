<?php namespace Andesite\CliCommand;

use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Andesite\Core\Constant\Constant;
use Andesite\Util\CodeFinder\CodeFinder;

class GenerateConstants extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config ) extends Command{

			private $config;
			public function __construct($config){
				parent::__construct('generate-constants');
				$this->config = $config;
			}

			protected function configure(){
				$this->setAliases(['gc']);
			}

			protected function execute(InputInterface $input, OutputInterface $output){
				$style = new SymfonyStyle($input, $output);

				$classes = array_key_exists('classes', $this->config) && is_array($this->config['classes']) ? $this->config['classes'] : [];

				$cf = CodeFinder::Service();

				if (array_key_exists('namespaces', $this->config) && is_array($this->config['namespaces'])){
					foreach ($this->config['namespaces'] as $namespace){
						$style->writeln($namespace);

						$classes = array_merge($classes, $cf->Psr4ClassSeeker($namespace));
					}
				}

				foreach ($classes as $class){
					if (is_subclass_of($class, Constant::class)){
						$style->writeln('loading '.$class);
						$class::generate();
					}
				}
				$style->success('done');
			}

		};
	}

}
