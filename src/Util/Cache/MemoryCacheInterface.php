<?php namespace Andesite\Util\Cache;

interface MemoryCacheInterface{
	public function add($object, $id);
	public function get($id);
	public function delete($id);
	public function clear();
}
