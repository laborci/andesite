<?php namespace Andesite\Util\ErrorHandler;

interface ExceptionHandlerInterface{
	public function handleException(\Throwable $e);
}