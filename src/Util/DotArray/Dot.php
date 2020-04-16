<?php namespace Andesite\Util\DotArray;

class Dot{

	public static function set(&$array, $key, $value, $isRef = false){
		$path = explode('.', $key);
		$work = &$array;
		foreach ($path as $segment){
			if (!array_key_exists($segment, $work)) $work[$segment] = [];
			$work = &$work[$segment];
		}
		$work = $value;
	}

	public static function has($array, $key){
		$path = explode('.', $key);
		$value = $array;
		foreach ($path as $segment){
			if (!array_key_exists($segment, $value)) return false;
			$value = $value[$segment];
		}
		return true;
	}

	public static function get(&$array, $key){
		$path = explode('.', $key);
		$value = $array;

		foreach ($path as $segment){
			if (!array_key_exists($segment, $value)) return null;
			$value = $value[$segment];
		}
		return $value;
	}

	public static function expode($array){
		$flatten = static::flatten($array);
		$values = [];
		foreach ($flatten as $path => $value){
			$keys = explode('.', $path);
			$storage = array_pop($keys);
			$base = &$values;
			foreach ($keys as $key){
				if (!array_key_exists($key, $base)) $base[$key] = [];
				$base = &$base[$key];
			}
			$base[$storage] = $value;
		}
		return $values;
	}

	public static function flatten($array){
		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array), \RecursiveIteratorIterator::SELF_FIRST);
		$path = [];
		$flatten = [];
		foreach ($iterator as $key => $value){
			$path[$iterator->getDepth()] = $key;
			$path = array_slice($path, 0, $iterator->getDepth() + 1);
			if (!is_array($value)) $flatten[join('.', $path)] = $value;
		}
		return $flatten;
	}

}