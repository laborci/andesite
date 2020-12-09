<?php namespace Andesite\Util\ErrorHandler;

use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Util\RemoteLog\RemoteLog;


class ErrorHandler{

	use Service;

	public function register(){
		$fatalErrorHandler = ServiceContainer::get(FatalErrorHandlerInterface::class);
		$errorHandler = ServiceContainer::get(ErrorHandlerInterface::class);
		$exceptionHandler = ServiceContainer::get(ExceptionHandlerInterface::class);

		if ($fatalErrorHandler){
			register_shutdown_function([$fatalErrorHandler, 'handleFatalError']);
		}

		if ($exceptionHandler){
			set_exception_handler([$exceptionHandler, 'handleException']);
			set_error_handler([$errorHandler, 'handleError'],E_ALL);
			//set_error_handler(function ($severity, $message, $file, $line){	throw new \ErrorException($message, $severity, $severity, $file, $line); });
		}

	}

}