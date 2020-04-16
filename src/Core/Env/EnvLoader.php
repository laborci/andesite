<?php namespace Andesite\Core\Env;

use Andesite\Core\ServiceManager\SharedService;
use Andesite\Util\YmlPlus\Loader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Andesite\Util\DotArray\Dot;

class EnvLoader implements SharedService{

	protected $env = [];

	public function rebuildCache($force = false){
		$cacheFile = getenv('env-file');
		$latestBuild = @filemtime($cacheFile);
		$dir = new \RecursiveDirectoryIterator(getenv('ini-path'));
		$iterator = new \RecursiveIteratorIterator($dir);
		foreach ($iterator as $fileinfo){
			if ($fileinfo->getMTime() > $latestBuild || $force){
				file_put_contents($cacheFile, "<?php return " . var_export($this->load(), true) . ';');
				return false;
			}
		}
		return true;
	}

	protected function load(){
		$loader = new Loader(getenv('ini-path'), ".local.yml");
		return $loader->load(getenv('ini-file'), [
			"root"                     => getenv('root'),
//			"path"                     => ["root" => getenv('root')],
//			"sys.missions @"           => "missions/*",
//			"sys.startup @"            => "~module/startup-modules",
//			"sys.modules @"            => "~module/base-modules",
//			"sys.module.aliases @"     => "~module/aliases",
//			"sys.module.configs @"     => "module/module-configs/*",
//			"cli/sys.module.configs @" => "module/cli-module-configs/*",
//			"path @"                   => "~config/path",
		]);
	}

}
