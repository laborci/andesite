<?php namespace Andesite\Mission\Cli;

use Andesite\Core\Module\Module;
use Symfony\Component\Console\Command\Command;

abstract class CliModule extends Module{

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;
	protected $mission;

	protected function load(CliMission $mission){
		$this->mission = $mission;
	}

	protected function run($config){
		$this->command = $this->createCommand($config);
		$this->mission->addCommand($this->command);
	}

	abstract protected function createCommand($config):Command;


}

