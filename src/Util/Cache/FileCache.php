<?php namespace Andesite\Util\Cache;

use Symfony\Polyfill\Mbstring\Mbstring;
class FileCache implements FileCacheInterface{

	protected $path;
	protected $ext;
	protected $cachedThisSession = [];

	public function __construct($path, $ext = 'txt') {
		if (!is_dir($path)) mkdir($path);
		$this->ext = '.' . $ext;
		$this->path = $path . '/';
	}

	public function set($key, $value, int $time = null) {
		$this->cachedThisSession[] = $key;
		$file = $this->file($key);
		file_put_contents($file, $value);
		if(!is_null($time)) touch($file, time()+$time);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists(string $key):bool { return file_exists($this->file($key)); }

	public function get(string $key):string { return file_get_contents($this->file($key)); }

	public function delete(string $key):void { if ($this->exists($key)) unlink($this->file($key)); }

	public function isCachedThisSession(string $key):bool { return in_array($key, $this->cachedThisSession); }

	/**
	 * @param string $key
	 * @return string path of the cache file
	 */
	public function file(string $key):string { return $this->path . $key . $this->ext; }
	/**
	 * Finds cache files
	 * @param string $pattern glob pattern
	 * @return array Full path of cache files
	 */
	public function find(string $pattern): array {
		$files = glob($this->path . $pattern . $this->ext);
		$items = [];
		foreach ($files as $file) {
			$info = pathinfo($file);
			$items[] = $info['filename'];
		}
		return $items;
	}

	/**
	 * Deletes cache files
	 * @param string $pattern glob pattern
	 */
	public function clear($pattern = '*'):void {
		$items = $this->find($pattern);
		foreach ($items as $item) {
			$this->delete($item);
		}
	}
	/**
	 * @param string $key
	 * @return false|int
	 */
	public function getTime(string $key) { return filemtime($this->file($key)); }
	/**
	 * @param string $key
	 * @return false|int
	 */
	public function getAge(string $key) { return time() - filemtime($this->file($key)); }
	/**
	 * @param string $key
	 * @return bool
	 */
	public function isValid(string $key):bool { return $this->exists($key) && $this->getAge($key)<0; }

}