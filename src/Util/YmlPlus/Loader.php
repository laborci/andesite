<?php namespace Andesite\Util\YmlPlus;

use Andesite\Util\DotArray\Dot;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Loader{

	public function __construct($path, ...$alternatives){
		$this->path = $path;
		$this->alternatives = $alternatives;
	}

	public function load($file, $defaults){
		$env = $this->loadYml($file, $defaults);
		$env = $this->resolveAbsolutes($env);
		$env = $this->resolveInterpolations($env);
		$env = array_merge($env, Dot::flatten($env));
		return $env;
	}

	protected function resolveAbsolutes($env){
		$env = Dot::flatten($env);
		foreach ($env as $key => $value){
			if (strpos($key,'/') !== false){
				[$dummy, $newkey] = explode('/', $key, 2);
				$env[$newkey] = $value;
				unset($env[$key]);
			}
		}
		return Dot::expode($env);
	}

	protected function resolveInterpolations($env){
		$flatten = Dot::flatten($env);
		$resolvables = [];
		foreach ($flatten as $key => $value){
			$key = trim($key);
			$value = trim($value);
			if (substr($key, -1) === '&'){
				$resolvables[$key] = [
					'type'  => '&',
					'key'   => trim(substr($key, 0, -1)),
					'value' => $value,
				];
				unset($flatten[$key]);
			}elseif (substr($key, -1) === '~'){
				$resolvables[$key] = [
					'type'  => '~',
					'key'   => trim(substr($key, 0, -1)),
					'value' => $value,
				];
				unset($flatten[$key]);
			}elseif (strpos($key, '~') !== false){
				[$newkey, $base] = explode('~', $key, 2);
				$resolvables[$key] = [
					'type'  => '~',
					'key'   => $newkey,
					'value' => $base . ' + "' . $value . '"',
				];
				unset($flatten[$key]);
			}
		}

		$env = Dot::expode($flatten);

		if (count($resolvables)) do{
			$count = count($resolvables);
			foreach ($resolvables as $rkey => $resolvable){
				$key = trim($resolvable['key']);
				$value = trim($resolvable['value']);
				$type = $resolvable['type'];
				switch ($type){
					case '~':
						if (substr($value, 0, 1) === '(') $value = substr($value, 1, -1);
						$concat = explode(' + ', $value);
						$parts = [];
						foreach ($concat as $part){
							if (substr($part, 0, 1) === '"' && substr($part, -1) === '"'){
								$parts[] = substr($part, 1, -1);
							}elseif (Dot::has($env, $part)){
								$parts[] = Dot::get($env, $part);
							}
						}
						if (count($parts) === count($concat)){
							Dot::set($env, $key, join('', $parts));
							unset($resolvables[$rkey]);
						}
						break;
					case '&':
						if (Dot::has($env, $value)){
							$target = '["' . join('"]["', explode('.', $key)) . '"]';
							$source = '["' . join('"]["', explode('.', $value)) . '"]';
							$eval = '$env' . $target . ' = &$env' . $source . ';';
							eval($eval);
							unset($resolvables[$rkey]);
						}
						break;
				}
			}
			if ($count === count($resolvables)){
				throw new \Exception('Env path reference not found "' . reset($resolvables)['key'] . reset($resolvables)['type'] . '"');
			}
		}while (count($resolvables));
		return $env;
	}

	protected function loadYml($file, $default = []){

		if(substr($file, 0, 1) === '~'){
			$optional = true;
			$file = substr($file, 1);
		}else{
			$optional = false;
		}

		$ini_file = $this->path . $file . '.yml';
		$values = $default;

		try{
			$loaded = Yaml::parseFile($ini_file);
			if (is_array($loaded)) $values = array_replace_recursive($values, $loaded);

			foreach ($this->alternatives as $alternative){
				$ini_file = $this->path . $file . $alternative;
				if (file_exists($ini_file)){
					$loaded = Yaml::parseFile($ini_file);
					if (is_array($loaded)) $values = array_replace_recursive($values, $loaded);
				}
			}
		}catch (ParseException $e){
			if(!$optional)	throw new \Exception($e->getMessage() . ' - in [[[' . $file . ']]] (' . $e->getParsedLine() . ')');
		}

		$env = Dot::flatten($values);

		// resolve multi imports into simple imports
		foreach ($env as $key => $value){
			if (substr($key, -1) === '@' && substr($value, -1) === '*'){
				$files = glob(getenv('ini-path') . $value . '.yml');
				$value = dirname($value) . '/';
				foreach ($files as $file) if (substr($file, -8, 4) !== '.ext' && substr($file, -10, 6) !== '.local'){
					$env = $this->array_splice_after_key($env, $key, [trim(substr($key, 0, -1)) . '.' . basename($file, '.yml') . ' @' => $value . basename($file, '.yml')]);
				}
				unset($env[$key]);
			}
		}

		// resolve fields and loads
		foreach ($env as $key => $value){
			if (substr($key, -1) === '@'){
				$includes = $value;
				$value = [];
				if (is_string($includes)) $includes = [$includes];
				foreach ($includes as $include){
					$value = array_replace_recursive($value, $this->loadYml($include));
				}
				unset($env[$key]);
				$key = trim(substr($key, 0, -1));
			}
			$env[$key] = $value;
		}

		return Dot::expode($env);
	}

	function array_splice_after_key($array, $key, $array_to_insert){
		$key_pos = array_search($key, array_keys($array));
		if ($key_pos !== false){
			$key_pos++;
			$second_array = array_splice($array, $key_pos);
			$array = array_merge($array, $array_to_insert, $second_array);
		}
		return $array;
	}

}
