<?php namespace Andesite\CodexGhostHelper;

class Translation{
	protected $dictionary = [];
	public function addFromAnnotations($data){ foreach ($data as $item) $this->add(...explode(':', $item, 2)); }
	public function add($key, $value){ $this->dictionary[trim($key)] = trim($value); }
	public function get($key, $default = null){ return array_key_exists($key, $this->dictionary) && $this->dictionary[$key] ? $this->dictionary[$key] : (is_null($default) ? $key : $default); }
}