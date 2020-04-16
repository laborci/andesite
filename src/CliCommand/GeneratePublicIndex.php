<?php namespace Andesite\CliCommand;

use Andesite\CodexGhostHelper\CodexHelperGenerator;
use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeneratePublicIndex extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config, 'generate:public-index', 'public', "Generates the web root" ) extends CliCommand{

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				$public = Env::Service()->get('path.public');
				if(!is_dir($public)) mkdir($public);
				file_put_contents($public.'index.php', '<?php include "../@src/index.php";');
				$style->success('Done.');
			}
		};
	}
}
