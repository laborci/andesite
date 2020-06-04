<?php namespace Andesite\Util\RemoteLog;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\SqlLogInterface;
use Andesite\Util\Dumper\DumpInterface;
use Andesite\Util\ErrorHandler\ExceptionHandlerInterface;
use Andesite\Util\ErrorHandler\FatalErrorHandlerInterface;

use Symfony\Component\HttpFoundation\Request;

class RemoteLog implements FatalErrorHandlerInterface, ExceptionHandlerInterface, DumpInterface, SqlLogInterface{

	/** @var RemoteLogSender  */
	private $sender;

	public function __construct(AbstractRemoteLogSender $sender){
		$this->sender = $sender;
	}

	public function handleFatalError(){
		$error = error_get_last();
		if ($error !== null){
			$this->sender->log('error', [
				'errorlevel' => $this->friendlyErrorType($error['type']),
				'message'    => $error['message'],
				'file'       => $error['file'],
				'line'       => $error['line'],
				'trace'      => [],
			]);
			exit;
		}
	}

	public function handleException(\Throwable $exception){
		$line = $exception->getLine();
		$file = $exception->getFile();
		$message = $exception->getMessage() . ' (' . $exception->getCode() . ')';
		$trace = $exception->getTrace();
		$type = get_class($exception);
		if ($exception instanceof \ErrorException){
			$ftrace = $trace[0];
			array_shift($trace);
			$this->sender->log('error', [
				'type'       => $type,
				'errorlevel' => array_key_exists('args', $ftrace) ? $this->friendlyErrorType($ftrace['args'][0]) : 'E_ERROR',
				'message'    => $message,
				'file'       => $file,
				'line'       => $line,
				'trace'      => $trace,
			]);
		}else{
			$this->sender->log('exception', [
				'type'    => $type,
				'message' => $message,
				'file'    => $file,
				'line'    => $line,
				'trace'   => $trace,
			]);
		}
	}

	public function dump(...$data){
		$trace = debug_backtrace();
		$this->sender->log('info', [
			'file'=> $trace[1]['file'],
			'line'=> $trace[1]['line'],
			'message'=>count($data) === 1 ? $data[0] : $data
		]);
	}
	public function logSql($sql){ $this->sender->log('sql', $sql); }

	protected function friendlyErrorType($type){
		switch ($type){
			case E_ERROR: // 1 //
				return 'E_ERROR';
			case E_WARNING: // 2 //
				return 'E_WARNING';
			case E_PARSE: // 4 //
				return 'E_PARSE';
			case E_NOTICE: // 8 //
				return 'E_NOTICE';
			case E_CORE_ERROR: // 16 //
				return 'E_CORE_ERROR';
			case E_CORE_WARNING: // 32 //
				return 'E_CORE_WARNING';
			case E_COMPILE_ERROR: // 64 //
				return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING: // 128 //
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR: // 256 //
				return 'E_USER_ERROR';
			case E_USER_WARNING: // 512 //
				return 'E_USER_WARNING';
			case E_USER_NOTICE: // 1024 //
				return 'E_USER_NOTICE';
			case E_STRICT: // 2048 //
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR: // 4096 //
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: // 8192 //
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED: // 16384 //
				return 'E_USER_DEPRECATED';
		}
		return "";
	}

}

