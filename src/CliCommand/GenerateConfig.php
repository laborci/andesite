<?php namespace Andesite\CliCommand;

use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class GenerateConfig extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'generate:config', 'vhost', 'Generates virtualhost and other configs' ) extends CliCommand{

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){

				$env = Env::Service();
				$files = $this->config;
				$loader = new FilesystemLoader();
				$twig = new Environment($loader);
				$loader->addPath($this->config['source'], '__main__');

				foreach ($this->config['translate'] as $template => $outfile){
					$output = $twig->render($template . '.twig', $env->get());
					file_put_contents($outfile, $output);
					$style->success($template . ' Done');
				}
			}
		};
	}

}