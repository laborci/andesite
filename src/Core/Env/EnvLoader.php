<?php namespace Andesite\Core\Env;

use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\SharedService;
use Andesite\Util\YmlPlus\Loader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Andesite\Util\DotArray\Dot;

class EnvLoader implements SharedService{

	use Service;

	private $env = [];

	public function rebuildCache($force = false){
		$cacheFile = getenv('env-file');
		$latestBuild = @filemtime($cacheFile);
		$dir = new \RecursiveDirectoryIterator(getenv('ini-path'));
		$iterator = new \RecursiveIteratorIterator($dir);
		foreach ($iterator as $fileinfo) if($fileinfo->isFile()){
			if ($fileinfo->getMTime() > $latestBuild || $force){
				file_put_contents($cacheFile, "<?php return " . var_export($this->load(), true) . ';');
				file_put_contents(str_replace('.php','.json',$cacheFile), json_encode($this->load(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE));
				return false;
			}
		}
		return true;
	}

	private function load(){
		$loader = new Loader(getenv('ini-path'), ".local.yml");
		return $loader->load(getenv('ini-file'), ["root" => getenv('root'),]);
	}

}
