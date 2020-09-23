<?php namespace Andesite\Util\PropertyList;

class PropertyListDefinition{

	private $fields;

	public function __construct(...$fields){
		foreach ($fields as $field) $this->fields[$field['name']] = $field;
	}

	public static function field($name, $default = '', $options = null){
		return [
			'name'    => $name,
			'default' => $default,
			'options' => $options,
		];
	}

	public function hasKey($key){ return array_key_exists($key, $this->fields); }
	public function getOptions($key){ return array_key_exists($key, $this->fields) ? $this->fields[$key]['options'] : null; }
	public function getDefault($key){ return array_key_exists($key, $this->fields) ? $this->fields[$key]['default'] : null; }
	public function getKeys(){ return array_keys($this->fields); }

	public function create($data){ return new PropertyList($this, $data);}

}