<?php namespace Andesite\TwigResponder;

use Andesite\Core\Module\Module;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigResponder extends Module{

	private ?Environment $twigEnvironment;
	private ?FilesystemLoader $twigLoader;
	private $twigSources = [];
	private $twigCache;
	private $twigDebug;
	private $language;
	private $clientVersionFile;

	protected function setup($config){
		if (array_key_exists('twig-sources', $config)) $this->twigSources = $config['twig-sources'];
		if (array_key_exists('twig-main', $config)) $this->twigSources['__main__'] = $config['twig-main'];
		$this->twigSources['smartpage'] = __DIR__ . '/@resource';
		$this->twigCache = $config['twig-cache'];
		$this->twigDebug = $config['twig-debug'];
		$this->language = $config['language'];
		$this->clientVersionFile = $config['client-version-file'];

		$loader = new FilesystemLoader();
		$twigEnvironment = new Environment($loader, ['cache' => $this->twigCache, 'debug' => $this->twigDebug]);
		foreach ($this->twigSources as $namespace => $source) $loader->addPath($source, $namespace);

		$this->twigLoader = $loader;
		$this->twigEnvironment = $twigEnvironment;
	}

	public function render($template, $viewModel){ return $this->twigEnvironment->render($template, $viewModel); }

	public function getClientVersion(){ return file_get_contents($this->clientVersionFile); }

	public function getLanguage(){ return $this->language; }
	public function addTwigPath($namespace, $path){ $this->twigLoader->addPath($path, $namespace); }
	public function addTwigFilter(TwigFilter $filter){ $this->twigEnvironment->addFilter($filter); }
	public function addTwigFunction(TwigFunction $function){ $this->twigEnvironment->addFunction($function); }

}