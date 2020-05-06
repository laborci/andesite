<?php namespace Andesite\Mission\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class CliCommand extends Command{

	protected $config;

	public function __construct($config, $name, $alias = null, $decription = null){
		parent::__construct($name);
		if (!is_null($alias)) $this->setAliases([$alias]);
		if (!is_null($decription)) $this->setDescription($decription);
		$this->config = $config;
		$this->addOption('show-config', "S");
	}

	protected final function execute(InputInterface $input, OutputInterface $output){
		$style = new SymfonyStyle($input, $output);
		if ($input->getOption('show-config')){
			if(is_array($this->config)) ConsoleTree::draw($this->config, $style, 'config');
			else $style->success('config: '.$this->config);
		}else{
			$this->runCommand($style, $input, $output, $this->config);
		}
	}

	abstract protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config);

}