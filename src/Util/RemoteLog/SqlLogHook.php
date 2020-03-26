<?php namespace Andesite\Util\RemoteLog;

use Andesite\DBAccess\Connection\SqlLogHookInterface;

class SqlLogHook implements SqlLogHookInterface{

	/** @var \Andesite\Util\RemoteLog\RemoteLog */
	private $logger;
	public function __construct(RemoteLog $logger){ $this->logger = $logger; }
	public function log($sql){ $this->logger->sql($sql); }

}