<?php namespace Andesite\Util\ErrorHandler;

interface ErrorHandlerInterface{
	public function handleError(int $errno , string $errstr , string $errfile , int $errline);
}