<?php namespace Andesite\Util\RemoteLog;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\SqlLogInterface;
use Andesite\Util\Dumper\DumpInterface;
use Andesite\Util\ErrorHandler\ExceptionHandlerInterface;
use Andesite\Util\ErrorHandler\FatalErrorHandlerInterface;

use Symfony\Component\HttpFoundation\Request;

class RemoteLog implements FatalErrorHandlerInterface, ExceptionHandlerInterface, DumpInterface, SqlLogInterface{

	protected $address;
	protected $requestId;

	public function __construct($address, $requestId){
		$this->address = $address . '/';
		$this->requestId = $requestId;
	}

	public function handleFatalError(){
		$error = error_get_last();
		if ($error !== null){
			$this->log('error', [
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
			$this->log('error', [
				'type'       => $type,
				'errorlevel' => $this->friendlyErrorType($ftrace['args'][0]),
				'message'    => $message,
				'file'       => $file,
				'line'       => $line,
				'trace'      => $trace,
			]);
		}else{
			$this->log('exception', [
				'type'    => $type,
				'message' => $message,
				'file'    => $file,
				'line'    => $line,
				'trace'   => $trace,
			]);
		}
	}

	public function dump($data){ $this->log('info', $data); }
	public function logSql($sql){ $this->log('sql', $sql); }

	protected function log($type, $message){
		$request = ServiceContainer::get(Request::class);
		$this->post_without_wait($this->address, [
			'request' => [
				'id'     => $this->requestId,
				'method' => $request->getMethod(),
				'host'   => $request->getHost(),
				'path'   => $request->getPathInfo(),
			],
			'type'    => $type,
			'message' => $message,
		]);
	}

	protected function post_without_wait($url, $message){
		$post_string = json_encode($message);
		$parts = parse_url($url);
		try{
			$fp = @fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
			if ($fp){
				$out = "POST " . $parts['path'] . " HTTP/1.1\r\n";
				$out .= "Host: " . $parts['host'] . "\r\n";
				$out .= "Content-Type: application/json\r\n";
				$out .= "Content-Length: " . strlen($post_string) . "\r\n";
				$out .= "Connection: Close\r\n\r\n";
				if (isset($post_string))
					$out .= $post_string;
				fwrite($fp, $out);
				fclose($fp);
			}
		}catch (\Exception $ex){
		}
	}

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

