<?php namespace Andesite\Mission\Cli;

use Andesite\Mission\Mission;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class CliMission extends Mission{

	/** @var \Symfony\Component\Console\Application */
	protected $application;

	public function __construct(){
		$this->application = new Application('Andesite', '1');
	}

	public function addCommand(Command $command){
		$this->application->add($command);
	}

	protected function run($config){
		$this->application->run();
	}
}