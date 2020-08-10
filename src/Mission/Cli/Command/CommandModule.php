<?php namespace Andesite\Mission\Cli\Command;

use Andesite\Mission\Cli\CliMission;
use Minime\Annotations\Reader;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;

abstract class CommandModule extends \Andesite\Core\Module\Module{

	protected $mission;
	protected Reader $reader;
	protected $config;

	public function __construct(Reader $reader){
		$this->reader = $reader;
	}

	protected function load(CliMission $mission){
		$this->mission = $mission;
	}

	protected function run($config){
		$this->config = $config;
		$classReflection = new ReflectionClass($this);
		$annotations = $this->reader->getAnnotations($classReflection);
		$commandGroup = $annotations->get('command-group', '');
		$functions = $classReflection->getMethods();
		foreach ($functions as $function){
			if ($function->hasReturnType() && !$function->getReturnType()->isBuiltin() && $function->getReturnType()->getName() === Cmd::class){
				$annotations = $this->reader->getAnnotations($function);
				/** @var \Andesite\Mission\Cli\Command\Cmd $command */
				$command = $function->invoke($this);
				if ($annotations->has('command')){
					$command->setName(( $commandGroup ? $commandGroup . ':' : '' ) . $annotations->get('command'));
				}else{
					$command->setName(( $commandGroup ? $commandGroup . ':' : '' ) . $function->getName());
				}
				if ($annotations->has('alias')){
					$command->setAliases($annotations->getAsArray('alias'));
				}
				if ($annotations->has('description')){
					$command->setDescription($annotations->get('description'));
				}
				$command->setConfig($config);
				$command->setCommandModule($this);
				CliMission::Module()->addCommand($command);
			}
		}
	}
}
