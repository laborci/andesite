<?php namespace Andesite\CliCommand;

use Andesite\Mission\Cli\CliModule;
use Andesite\Zuul\RoleManager\RoleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateGroups extends CliModule{

	protected function createCommand($config): Command{
		return new class($config) extends Command{
			private $config;
			public function __construct($config){
				parent::__construct('update-groups');
				$this->config = $config;
			}

			protected function configure(){
				$this->setAliases(['ug']);
			}

			protected function execute(InputInterface $input, OutputInterface $output){
				$roleManager = RoleManager::Module();
				$style = new SymfonyStyle($input, $output);
				/** @var \Andesite\Ghost\Model $model */
				$model = $this->config['user-ghost']::$model;

				/** @var \Andesite\DBAccess\Connection\PDOConnection $connection */
				$connection = $model->connection;
				$table = $model->table;
				$field = $this->config['group-field'];
				$connection->query("ALTER TABLE `".$table."` CHANGE `".$field."` `".$field."` SET('".join("','",$roleManager->getGroups() )."') NULL  DEFAULT NULL;");
				$style->success('Done');
			}
		};
	}
}