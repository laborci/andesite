<?php namespace Andesite\Util\RemoteLog;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\SqlLogInterface;
use Andesite\Util\Dumper\DumpInterface;
use Andesite\Util\ErrorHandler\ExceptionHandlerInterface;
use Andesite\Util\ErrorHandler\FatalErrorHandlerInterface;

use Symfony\Component\HttpFoundation\Request;

class RemoteLogSenderSocket extends AbstractRemoteLogSender{

	protected function send($address, $message){
		$socket = stream_socket_client('unix://' . $address, $errorCode, $errorMessage, 12);
		fwrite($socket, json_encode($message));
		fclose($socket);
	}

}

