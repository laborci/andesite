<?php namespace Andesite\CliCommand;

use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Andesite\Core\Constant\Constant;
use Andesite\Util\CodeFinder\CodeFinder;

class GenerateConstants extends CommandModule{
	
	/**
	 * @command       constants
	 * @alias const
	 * @description   Generates constants
	 */
	public function env(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$classes = array_key_exists('classes', $this->config) && is_array($this->config['classes']) ? $this->config['classes'] : [];

				$cf = CodeFinder::Service();

				if (array_key_exists('namespaces', $this->config) && is_array($this->config['namespaces'])){
					foreach ($this->config['namespaces'] as $namespace){
						$this->style->writeln($namespace);

						$classes = array_merge($classes, $cf->Psr4ClassSeeker($namespace));
					}
				}

				foreach ($classes as $class){
					if (is_subclass_of($class, Constant::class)){
						$this->style->writeln('loading ' . $class);
						$class::generate();
					}
				}
				$this->style->success('done');
			}
		} );
	}
}

