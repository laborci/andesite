<?php namespace Andesite\Core\Session;

use Andesite\Core\ServiceManager\SharedService;

abstract class Session implements SharedService{

	private $fields;
	private $namespace;

	public function __construct() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$this->fields = $this->getFields();
		$this->namespace = $this->getNamespace();
		if (!array_key_exists($this->namespace, $_SESSION)) $this->forget();
		$this->load();
		register_shutdown_function([$this, 'flush']);
	}

	private function load() {
		foreach ($this->fields as $field) {
			if(array_key_exists($field, $_SESSION[$this->namespace])) $this->$field = $_SESSION[$this->namespace][$field];
		}
	}

	public function forget() {
		$_SESSION[$this->namespace] = [];
		foreach ($this->fields as $field) {
			$_SESSION[$this->namespace][$field] = $this->$field = null;
		}
	}

	public function flush() {
		foreach ($this->fields as $field) {
			$_SESSION[$this->namespace][$field] = $this->$field;
		}
	}

	private function getFields() {
		$fields = [];
		$properties = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
		foreach ($properties as $property) {

			$fields[] = $property->name;
		}
		return $fields;
	}

	protected function getNamespace() { return static::class; }

}