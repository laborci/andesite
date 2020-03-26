<?php namespace Andesite\Util\CodeFinder;

use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Core\ServiceManager\SharedService;
use Composer\Autoload\ClassLoader;


class CodeFinder implements SharedService{

	use Service;

	public function Psr4ClassSeeker($namespace, $pattern = '*.php'){
		$path = $this->Psr4ResolveNamespace($namespace);
		return array_map(
			function ($file) use ($namespace, $path){ return $namespace . "\\" . str_replace("/", "\\", substr($file, strlen($path), -4)); },
			$this->fileSeeker($path, $pattern)
		);
	}

	public function Psr4FileSeeker($namespace, $pattern = '*.php'){
		$path = $this->Psr4ResolveNamespace($namespace);
		return !is_null($path) ? $this->fileSeeker($path, $pattern) : [];
	}

	public function Psr4ResolveNamespace($namespace){
		$path = $this->Psr4Resolve($namespace);
		return !is_null($path) ? $path . '/' : null;
	}

	public function Psr4ResolveClass($class){
		$path = $this->Psr4Resolve($class);
		return !is_null($path) ? $path . '.php' : null;
	}

	public function Psr4Resolve($name){
		/** @var ClassLoader $cl */
		$cl = ServiceContainer::get(ClassLoader::class);
		$prefixesPsr4 = $cl->getPrefixesPsr4();
		$segments = explode('\\', $name);
		$relpath = [];
		$path = null;

		do{
			$ns = join('\\', $segments) . '\\';
			$path = array_key_exists($ns, $prefixesPsr4) ? $prefixesPsr4[$ns][0] : null;
			if (!is_null($path)){
				return ($path . "/" . join("/", $relpath));
			}
			array_unshift($relpath, array_pop($segments));
		}while (!empty($segments));

		return null;
	}

	public function fileSeeker($path, $pattern = '*'){
		$result = [];
		$files = glob($path . $pattern);
		foreach ($files as $file) if (is_file($file)) $result[] = $file;
		$dirs = glob($path . '*', GLOB_ONLYDIR);
		foreach ($dirs as $dir) $result = array_merge($result, static::fileSeeker($dir . '/', $pattern));
		return $result;
	}

}