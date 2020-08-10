<?php namespace Andesite\CliCommand\Migrate;

class ViewDiff{
	public $name;
	public $from;
	public $to;

	public function __construct($name, $from, $to){
		$this->name = $name;
		$this->from = $from;
		$this->to = $to;
	}

	public function up(){
		if ($this->to === $this->from) return null;
		if ($this->to === '') return "DROP VIEW IF EXISTS `" . $this->name . "`;";
		return "CREATE OR REPLACE VIEW `" . $this->name . "` AS " . $this->to . ';';
	}

	public function down(){
		if ($this->to === $this->from) return null;
		if ($this->from === '') return "DROP VIEW IF EXISTS `" . $this->name . "`;";
		return "CREATE OR REPLACE VIEW `" . $this->name . "` AS " . $this->from . ';';
	}
}