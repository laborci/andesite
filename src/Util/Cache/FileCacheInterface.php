<?php namespace Andesite\Util\Cache;

interface FileCacheInterface{
	public function __construct($path, $ext = 'txt');
	public function set(string $key, $value, int $time = null);
	public function exists(string $key):bool ;
	public function get(string $key):string;
	public function delete(string $key);
	public function isCachedThisSession(string $key):bool;
	public function file(string $key):string;
	public function find(string $pattern):array ;
	public function clear(string $pattern = '*'):void ;
	public function getTime(string $key);
	public function getAge(string $key);
	public function isValid(string $key):bool;
}