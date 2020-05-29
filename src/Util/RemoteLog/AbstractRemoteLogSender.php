<?php namespace Andesite\Util\RemoteLog;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\SqlLogInterface;
use Andesite\Util\Dumper\DumpInterface;
use Andesite\Util\ErrorHandler\ExceptionHandlerInterface;
use Andesite\Util\ErrorHandler\FatalErrorHandlerInterface;

use Symfony\Component\HttpFoundation\Request;

abstract class AbstractRemoteLogSender{

	public function __construct($address, $requestId, $method, $host, $path){
		$this->address = $address . '/';
		$this->requestId = $requestId;
		$this->method = $method;
		$this->host = $host;
		$this->path = $path;
	}

	public function log($type, $message){
		$this->send($this->address, [
			'request' => [
				'id'     => $this->requestId,
				'method' => $this->method,
				'host'   => $this->host,
				'path'   => $this->path,
			],
			'type'    => $type,
			'message' => $message,
		]);
	}

	abstract protected function send($address, $message);

}

