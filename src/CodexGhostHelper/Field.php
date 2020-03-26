<?php namespace Andesite\CodexGhostHelper;

class Field{

	public $type;
	public $name;
	public $options = [];
	public $translation;

	public function __construct($type, $name, $options, Translation $translation){
		$this->type = $type;
		$this->name = $name;
		$this->translation = $translation;
		$this->options = is_array($options) ? $options : [];
	}

	public function getTranslateAnnotations(){
		$annotations = [" * @label-" . $this->type . " " . $this->name . ": " . $this->translation->get($this->name, '')];
		foreach ($this->options as $optkey) $annotations[] = " * @label-" . $this->type . " " . $this->name . '.' . $optkey . ": " . $this->translation->get($this->name . '.' . $optkey, '');
		return $annotations;
	}

	public function getField(){ return "\t/** @var Field */ protected $" . $this->name . ";"; }

	public function getFieldConstructor(){
		return
			"\t\t\$this->" . $this->name . " = new Field('" . $this->name . "', '" . $this->translation->get($this->name) . "'"
			. (!empty($this->options) ? ', [' . join(', ', array_map(function ($key){ return "'" . $key . "'=>'" . $this->translation->get($this->name . '.' . $key, $key) . "'"; }, $this->options)) . ']' : "") . ");";;
	}
}