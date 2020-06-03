<?php namespace Andesite\Util\RemoteLog;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\SqlLogInterface;
use Andesite\Util\Dumper\DumpInterface;
use Andesite\Util\ErrorHandler\ExceptionHandlerInterface;
use Andesite\Util\ErrorHandler\FatalErrorHandlerInterface;

use Symfony\Component\HttpFoundation\Request;

class RemoteLogScreen extends AbstractRemoteLogSender{

	protected function send($address, $message){
		register_shutdown_function(function () use ($message){
			echo '<script>';
			if ($message['type'] === 'exception'){
				echo 'console.group("[' . strtoupper($message['type']) . ']");';
				echo 'console.warn("[' . $message['message']['type'] . ']\n\n' . $message['message']['message'] . '\n\n' . $message['message']['file'] . ' @ ' . $message['message']['line'] . '");';
				echo 'console.groupCollapsed("Trace");';
				foreach ($message['message']['trace'] as $trace){
					echo 'console.info(' . json_encode($trace) . ');';
				}
				echo 'console.groupEnd();';
				echo 'console.groupEnd();';
			}else{
				echo 'console.group("[' . strtoupper($message['type']) . ']");';
				echo 'console.log(' . json_encode($message['message']) . ');';
				echo 'console.groupEnd();';

			}
			echo '</script>';
		});
	}

}

