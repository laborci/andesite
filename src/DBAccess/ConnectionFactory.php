<?php namespace Andesite\DBAccess;

use Andesite\Core\Env\Env;
use Andesite\Core\Module\Module;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\Dumper;
use Andesite\DBAccess\Connection\PDOConnection;
use Andesite\DBAccess\Connection\SqlLogHookInterface;

class ConnectionFactory extends Module{

	/** @var PDOConnection[] */
	protected $connections = [];
	protected $config = [];

	protected function setup($config){$this->config = $config;}

	public function getDumper($name, $path){
		$connection = $this->get($name);
		return new Dumper($connection, $path, Env::Service()->get('path.tmp'));
	}

	public function get($name): ?PDOConnection{
		if (!array_key_exists($name, $this->connections)){
			$connection = null;
			if (array_key_exists($name, $this->config['databases'])){
				$connection = $this->factory($this->config['databases'][$name]);
			}
			$this->connections[$name] = $connection;
		}
		return $this->connections[$name];
	}

	protected function factory($url): PDOConnection{

		$url = parse_url($url);

		$scheme = $url['scheme'];
		$host = $url['host'];
		$port = $url['port'];
		$user = $url['user'];
		$pass = $url['pass'];
		$database = trim($url['path'],'/');
		parse_str($url['query'], $options);
		$charset = $options['charset'];

		$connection = new PDOConnection("{$scheme}:host={$host};dbname={$database};port={$port};charset={$charset}", $user, $pass);

		$connection->setAttribute(PDOConnection::ATTR_PERSISTENT, true);
		$connection->setAttribute(PDOConnection::ATTR_ERRMODE, PDOConnection::ERRMODE_EXCEPTION);
		$connection->setAttribute(PDOConnection::ATTR_EMULATE_PREPARES, false);
		$connection->query("SET CHARACTER SET $charset");

		return $connection;
	}

}