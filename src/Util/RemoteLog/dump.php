<?php

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Util\RemoteLog\RemoteLog;

function dump(...$messages){
	$remoteLog = ServiceContainer::get(RemoteLog::class);
	foreach ($messages as $message){
		(function (RemoteLog $remoteLog, $message){ $remoteLog->dump($message); })($remoteLog, $message);
	}
}
