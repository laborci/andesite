<?php namespace Andesite\CliCommand;

use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;


class GenerateConfig extends CommandModule{

	/**
	 * @command       config
	 * @alias         vhost
	 * @description   Generates virtualhost and other configs
	 */
	public function env(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){

				$config = $this->config;
				$env = Env::Service();
				$loader = new FilesystemLoader();
				$twig = new Environment($loader);
				$loader->addPath($config['source'], '__main__');

				foreach ($config['translate'] as $template => $outfile){
					$output = $twig->render($template . '.twig', $config['data']);

					$lines = explode("\n", $output);
					$formattedLines = [];
					$level = 0;
					foreach ($lines as $line){
						$line = trim($line);
						if ($line !== ''){
							if (substr($line, 0, 2) === "</") $level--;
							$formattedLines[] = str_repeat("\t", $level) . $line;
							if ($line[0] === '<' && $line[1] !== "/") $level++;
						}
					}
					$output = join("\n", $formattedLines);

					file_put_contents($outfile, $output);
					$this->style->success($template . ' Done');
				}
			}
		} );
	}
}
