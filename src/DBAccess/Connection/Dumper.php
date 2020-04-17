<?php namespace Andesite\DBAccess\Connection;

use Rah\Danpu\Dump;
use Rah\Danpu\Export;

class Dumper{

	/** @var \Rah\Danpu\Dump  */
	protected $dumper;
	protected $path;

	public function getDumper():Dump{return $this->dumper;}

	public function __construct(PDOConnection $connection, $path, $tmp) {
		$this->dumper = new Dump();
		$this->path = $path;
		$this->dumper
			->dsn($connection->getDsn())
			->user($connection->getUsername())
			->pass($connection->getPasswd())
			->tmp($tmp)
		;
	}
	public function dump($file){
		$this->dumper->structure(true)->disableForeignKeyChecks(true)->data(true)->file($this->path.$file);
		new Export($this->dumper);
	}
	public function structure($file){
		$this->dumper->structure(true)->disableForeignKeyChecks(true)->data(false)->file($this->path.$file);
		new Export($this->dumper);
	}
	public function data($file){
		$this->dumper->structure(false)->disableForeignKeyChecks(true)->data(true)->file($this->path.$file);
		new Export($this->dumper);
	}
}
