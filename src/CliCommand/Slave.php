<?php namespace Andesite\CliCommand;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Util\Alert\AlertInterface;
use Andesite\Util\Cron\AbstractTask;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Twig\Compiler;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;

class Slave extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'slave', null, "generates codes from templates" ) extends CliCommand{

			protected function configure(){
				$this->addArgument('template', InputArgument::OPTIONAL);
			}
			
			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				chdir($config['templates']);
				$dirs = glob('*', GLOB_ONLYDIR);

				$template = $input->getArgument('template');
				if($template && !is_dir($config['templates'].'/'.$template) || !$template){
					$template = $style->choice("Select template", $dirs);
				}
				chdir($template);

				$filesystemloader = new FilesystemLoader();
				$twig = new Environment($filesystemloader);
				$filesystemloader->addPath(getcwd(), '__main__');

				$generator = include 'generator.php';
				if(!is_callable($generator)){
					$style->error('Generator is not invocable!');
				}else{
					$generator($style, $twig);
				}
			}

		};
	}

}
