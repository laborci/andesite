<?php namespace Andesite\CliCommand;

use Andesite\Core\Env\Env;
use Andesite\Mission\Cli\CliModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowEnv extends CliModule{

	protected function createCommand($config): Command{
		return new class( $config ) extends Command{

			private $config;

			public function __construct($config){
				parent::__construct();
				$this->config = $config;
			}

			protected function configure(){
				$this
					->setName('show-env')
					->setAliases(['showenv', 'se'])
					->setDescription('Creates ghost entities');
			}

			protected function execute(InputInterface $input, OutputInterface $output){
				$style = new SymfonyStyle($input, $output);
				$arr = array_filter(Env::Service()->get(), function ($key){ return strpos($key, '.') === false; }, ARRAY_FILTER_USE_KEY);
				//$arr = DotArray::flatten($arr);
				ksort($arr);
				$keys = array_keys($arr);
				$last_key = end($keys);
				foreach ($arr as $key => $value){
					$env[] = [$key === $last_key ? ' └─' : ' ├─', $key, is_array($value) ? null : ( is_null($value) ? '' : $value )];
					if (is_array($value)) $this->tree($value, $env, [$key === $last_key]);

				}
				$style->writeln("\n".'<fg=cyan>[env]</>');
				foreach ($env as $item){
					$style->write('' . $item[0] . ' ');
					if (is_null($item[2])){
						$style->writeln('<fg=cyan>' . $item[1] . '</>');
					}else{
						$style->write('<options=bold;fg=green>' . $item[1] . '</>: ');
						if ($item[2] === ''){
							$style->writeln('<fg=red;options=bold>-</>');
						}else{
							$style->writeln('<fg=black>' . $item[2] . '</>');
						}
					}
				}
				/*
				$table = new Table($output);
				$table
					->setHeaders(['key', 'value'])
					->setRows($env);
				$table->render();
			*/
			}

			protected function tree($branch, &$env, $level){
				$keys = array_keys($branch);
				$last_key = end($keys);
				foreach ($branch as $key => $value){
					$leaf = '';
					for ($i = 0; $i < count($level); $i++){
						$leaf .= $level[$i] ? '   ' : ' │ ';
					}
					$leaf .= $last_key === $key ? ' └─' : ' ├─';
					$env[] = [$leaf, $key, is_array($value) ? null : ( is_null($value) ? '' : $value )];
					$l = $level;
					$l[] = ( $key === $last_key );
					if (is_array($value)) $this->tree($value, $env, $l);
				}
			}
		};
	}

}
