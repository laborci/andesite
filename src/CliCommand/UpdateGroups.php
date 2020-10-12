<?php namespace Andesite\CliCommand;

use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Auth\RoleManager\RoleManager;
use Andesite\Mission\Cli\Command\Cmd;
use Andesite\Mission\Cli\Command\CommandModule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class UpdateGroups extends CommandModule{
	/**
	 * @command       groups
	 * @description   Generate user db field by constants
	 */
	public function updateGroups(): Cmd{
		return ( new class extends Cmd{
			public function __invoke(){
				$roleManager = RoleManager::Module();
				$userGhost = $roleManager->getUserGhost();
				/** @var \Andesite\Ghost\Model $model */
				$model = $userGhost::$model;

				/** @var \Andesite\DBAccess\Connection\PDOConnection $connection */
				$connection = $model->connection;
				$table = $model->table;
				$field = $roleManager->getGroupField();
				$type = $roleManager->isMultiGroup() ? 'SET' : 'ENUM';
				$connection->query("ALTER TABLE `" . $table . "` CHANGE `" . $field . "` `" . $field . "` " . $type . "('" . join("','", $roleManager->getGroups()) . "') NULL  DEFAULT NULL;");
				$this->style->success('Done');
			}
		} );
	}
}