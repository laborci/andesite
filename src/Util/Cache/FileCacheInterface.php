<?php namespace Andesite\Util\Cache;

interface FileCacheInterface{
	public function __construct($path, $ext = 'txt');
	public function set($key, $value, int $time = null);
	public function exists($key);
	public function get($key);
	public function delete($key);
	public function isCachedThisSession($key);
	public function file($key);
	public function find($pattern);
	public function clear($pattern = '*');
	public function getTime($key);
	public function getAge($key);
	public function isValid($key);
}