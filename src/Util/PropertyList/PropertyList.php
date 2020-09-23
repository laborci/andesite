<?php namespace Andesite\Util\PropertyList;

class PropertyList implements \JsonSerializable{

	private PropertyListDefinition $definition;
	private $data = [];

	public function __construct(PropertyListDefinition $definition, $data = null){
		$this->definition = $definition;
		foreach ($definition->getKeys() as $key) $this->data[$key] = $definition->getDefault($key);
		if (!is_null($data)) foreach ($definition->getKeys() as $key) if (array_key_exists($key, $data)) $this->set($key, $data[$key]);
	}

	public function get($key = null){ return is_null($key) ? $this->data : ($this->definition->hasKey($key) && array_key_exists($key, $this->data) ? $this->data[$key] : null); }

	public function set($key, $value){
		if ($this->definition->hasKey($key)){
			$options = $this->definition->getOptions($key);
			$value = strval($value);
			if (is_null($options) || in_array($value, $options)) $this->data[$key] = $value;
		}
	}

	public function jsonSerialize(){ return $this->data; }
}

