<?php namespace Andesite\Mission\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Cmd extends Command{

	protected $config;
	protected SymfonyStyle $style;
	protected InputInterface $input;
	protected OutputInterface $output;
	protected CommandModule $module;

	public function setConfig($config){ $this->config = $config; }
	public function setCommandModule(CommandModule $module){ $this->module = $module; }

	protected final function execute(InputInterface $input, OutputInterface $output){
		$this->style = new SymfonyStyle($input, $output);
		$this->input = $input;
		$this->output = $output;
		$this();
	}

	abstract public function __invoke();

}
