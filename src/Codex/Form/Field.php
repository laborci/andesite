<?php namespace Andesite\Codex\Form;

class Field{

	public $name;
	public $options;
	public $label;

	public function __construct($name, $label = null, $options = null){
		$this->name = $name;
		$this->label = $label ?: $name;
		$this->options = $options;
	}

	public function __toString(){ return $this->name; }

}