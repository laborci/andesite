<?php namespace Andesite\Util\Cache;

class MemoryCache implements MemoryCacheInterface{

	private $cache = [];

	public function add($object, $id) { $this->cache[$id] = $object; }
	public function get($id) { return array_key_exists($id, $this->cache) ? $this->cache[$id] : null; }
	public function delete($id) { unset($this->cache[$id]); }
	public function clear(){
		unset($this->cache);
		$this->cache = [];
	}

}