<?php namespace Andesite\TwigResponder;

use Andesite\Core\Module\Module;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class TwigResponder extends Module{

	private $twigEnvironment;
	private $twigCache;
	private $twigDebug;
	private $language;
	private $clientVersionFile;
	private $twigSources = [];

	protected function setup($config){
		if (array_key_exists('twig-sources', $config)) $this->twigSources = $config['twig-sources'];
		if (array_key_exists('twig-main', $config)) $this->twigSources['__main__'] = $config['twig-main'];
		$this->twigSources['smartpage'] = __DIR__ . '/@resource';
		$this->twigCache = $config['twig-cache'];
		$this->twigDebug = $config['twig-debug'];
		$this->language = $config['language'];
		$this->clientVersionFile = $config['client-version-file'];
	}

	public function render($template, $viewModel){
		if (is_null($this->twigEnvironment)){
			$loader = new FilesystemLoader();
			$twigEnvironment = new Environment($loader, ['cache' => $this->twigCache, 'debug' => $this->twigDebug]);
			foreach ($this->twigSources as $namespace => $source){
				$loader->addPath($source, $namespace);
			}
			if ($this->twigDebug) $twigEnvironment->addExtension(new DebugExtension());
			$this->twigEnvironment = $twigEnvironment;
		}
		return $this->twigEnvironment->render($template, $viewModel);
	}

	public function addTwigPath($namespace, $path){ $this->twigSources[$namespace] = $path; }
	public function getClientVersion(){ return file_get_contents($this->clientVersionFile); }
	public function getLanguage(){ return $this->language; }

}