<?php namespace Andesite\DBAccess\Connection;

interface SqlLogHookInterface{
	public function log($sql);
}