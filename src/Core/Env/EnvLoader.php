<?php namespace Andesite\Core\Env;

use Andesite\Core\ServiceManager\SharedService;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Andesite\Util\DotArray\DotArray;

class EnvLoader implements SharedService{

	protected $env = [];

	public function checkCache($rebuild = false){
		$cacheFile = getenv('env-file');
		if (!file_exists($cacheFile) && !$rebuild) return false;

		$latestBuild = @filemtime($cacheFile);
		$dir = new \RecursiveDirectoryIterator(getenv('ini-path'));
		$iterator = new \RecursiveIteratorIterator($dir);
		foreach ($iterator as $fileinfo){
			if ($fileinfo->getMTime() > $latestBuild){
				if ($rebuild) $this->rebuild();
				return false;
			}
		}
		return true;
	}

	protected function rebuild(){
		$content = "<?php return " . var_export($this->load(), true) . ';';
		file_put_contents(getenv('env-file'), $content);
	}

	protected function load(){
		$env = $this->loadYml(getenv('ini-file'));
		$env['root'] = $env['path']['root'] = getenv('root');
		$env = DotArray::flatten($env);
		$env = $this->resolveAbsolutes($env);
		$env = $this->resolveInterpolations($env);

		foreach ($env as $key => $value) DotArray::set($env, $key, $value);
		return $env;
	}

	protected function resolveAbsolutes($env){
		foreach ($env as $key => $value){
			if (strpos($key, '/') !== false){
				[$path, $newkey] = explode('/', $key, 2);
				$env[$newkey] = $value;
				unset($env[$key]);
			}
		}
		return $env;
	}

	protected function resolveInterpolations($env){
		$resolvables = [];
		foreach ($env as $key => $value){
			if (substr($key, -1) === '&' || strpos($key, '~') !== false){
				$resolvables[$key] = $value;
				unset($env[$key]);
			}
		}

		if (count($resolvables)) do{
			$count = count($resolvables);
			foreach ($resolvables as $key => $value){
				if (substr($key, -1) === '&'){
					if (array_key_exists($value, $env)){
						$env[trim(substr($key, 0, -1))] = $env[$value];
						unset($resolvables[$key]);
					}
				}elseif (substr($key, -1) === '~'){
					if (substr($value, 0, 1) === '(') $value = substr($value, 1, -1);
					$concat = explode(' + ', $value);
					$parts = [];
					foreach ($concat as $part){
						if (substr($part, 0, 1) === '"' && substr($part, -1) === '"'){
							$parts[] = substr($part, 1, -1);
						}elseif (array_key_exists($part, $env)){
							$parts[] = $env[$part];
						}
					}
					if (count($parts) === count($concat)){
						$env[trim(substr($key, 0, -1))] = join('', $parts);
						unset($resolvables[$key]);
					}
				}else{
					[$newkey, $base] = explode('~', $key, 2);
					$newkey = trim($newkey);
					$base = trim($base);
					if (array_key_exists($base, $env)){
						$env[$newkey] = $env[$base] . $value;
						unset($resolvables[$key]);
					}
				}
			}
			if ($count === count($resolvables)){
				throw new \Exception('Env path reference not found "' . array_keys($resolvables)[0] . '" as "' . $resolvables[array_keys($resolvables)[0]] . '"');
			}
		}while (count($resolvables));

		return $env;
	}

	protected function loadYml($file, $default = []){
		$ini_file = getenv('ini-path') . $file . '.yml';
		$ini_ext = getenv('ini-path') . $file . '.ext.yml';
		$ini_local = getenv('ini-path') . $file . '.local.yml';

		$values = $default;

		try{
			$file = $ini_file;
			$loaded = Yaml::parseFile($file);

			if (is_array($loaded)) $values = array_replace_recursive($values, $loaded);
			if (file_exists($ini_ext)){
				$file = $ini_ext;
				$loaded = Yaml::parseFile($ini_ext);
				if (is_array($loaded)) $values = array_replace_recursive($values, $loaded);
			}
			if (file_exists($ini_local)){
				$file = $ini_local;
				$loaded = Yaml::parseFile($ini_local);
				if (is_array($loaded)) $values = array_replace_recursive($values, $loaded);
			}
		}catch (ParseException $e){
			throw new \Exception($e->getMessage() . ' - in [[[' . $file . ']]] (' . $e->getParsedLine() . ')');
		}
		foreach ($values as $key => $value) DotArray::set($env, $key, $value);
		$env = DotArray::flatten($env);

		// resolve batch load
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
			if (is_array($value)) $value = array_replace_recursive(DotArray::get($env, $key, $value));
			DotArray::set($env, $key, $value);
		}

		return DotArray::flatten($env);
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