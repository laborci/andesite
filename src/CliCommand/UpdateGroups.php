<?php namespace Andesite\CliCommand;

use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Zuul\RoleManager\RoleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateGroups extends CliModule{

	protected function createCommand($config): Command{
		return new class($config, 'update:groups', null, 'Updates user groups') extends CliCommand{
			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				$roleManager = RoleManager::Module();
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