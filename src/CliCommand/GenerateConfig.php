<?php namespace Andesite\CliCommand;

use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateConfig extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config ) extends Command{
			private $config;
			public function __construct($config){
				parent::__construct('generate-config');
				$this->config = $config;
			}

			protected function configure(){
				$this->setAliases(['vhost']);
			}
			protected function execute(InputInterface $input, OutputInterface $output){
				$style = new SymfonyStyle($input, $output);
				$env = Env::Service();
				$files = $this->config;

				foreach ($files as $name => $file){
					$source = $file['template'];
					$target = $file['output'];

					$template = file_get_contents($source);
					preg_match_all('/\{\{(.*?)\}\}/', $template, $matches);
					$keys = array_unique($matches[1]);
					foreach ($keys as $key) $template = str_replace('{{' . $key . '}}', $env->get($key), $template);
					file_put_contents($target, $template);
					$style->success($name . ' Done');

				}

			}
		};
	}
}