<?php namespace Andesite\Util\RemoteLog;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\SqlLogInterface;
use Andesite\Util\Dumper\DumpInterface;
use Andesite\Util\ErrorHandler\ExceptionHandlerInterface;
use Andesite\Util\ErrorHandler\FatalErrorHandlerInterface;

use Symfony\Component\HttpFoundation\Request;

class RemoteLogSenderSocket extends AbstractRemoteLogSender{

	private $connection;

	public function __construct($address, $requestId, $method, $host, $path){
		parent::__construct($address, $requestId, $method, $host, $path);
		$this->connection = stream_socket_client('unix://' . $address, $errorCode, $errorMessage, 12);
	}

	public function hasResource(){
		return is_resource($this->connection);
	}

	protected function send($address, $message){
		$this->connection = stream_socket_client('unix://' . $address, $errorCode, $errorMessage, 12);
		$message = json_encode($message);
		$pieces = str_split($message, 1024);
		foreach ($pieces as $piece) fwrite($this->connection, $piece);
		fclose($this->connection);
	}

}

