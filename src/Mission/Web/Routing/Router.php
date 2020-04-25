<?php namespace Andesite\Mission\Web\Routing;

use Andesite\Mission\Web\Pipeline\DummyPipeline;
use Andesite\Mission\Web\Pipeline\Pipeline;
use Andesite\Core\ServiceManager\Service;
use Symfony\Component\HttpFoundation\Request;

class Router{

	use Service;

	/** @var Request */
	private $request;
	private $pipeline = [];

	/** @return Request */
	public function getRequest(): Request{ return $this->request; }

	function __construct(Request $request){ $this->request = $request; }

	public function get($pattern, $responderClass = null, $arguments = []): Pipeline{ return $this->route(Request::METHOD_GET, $pattern, $responderClass, $arguments); }
	public function post($pattern, $responderClass = null, $arguments = []): Pipeline{ return $this->route(Request::METHOD_POST, $pattern, $responderClass, $arguments); }
	public function delete($pattern, $responderClass = null, $arguments = []): Pipeline{ return $this->route(Request::METHOD_DELETE, $pattern, $responderClass, $arguments); }
	public function put($pattern, $responderClass = null, $arguments = []): Pipeline{ return $this->route(Request::METHOD_PUT, $pattern, $responderClass, $arguments); }
	public function patch($pattern, $responderClass = null, $arguments = []): Pipeline{ return $this->route(Request::METHOD_PATCH, $pattern, $responderClass, $arguments); }
	public function any($pattern, $responderClass = null, $arguments = []): Pipeline{ return $this->route('*', $pattern, $responderClass, $arguments); }
	public function api($path, $namespace){ return $this->route('*', rtrim($path, '/') . '/{path}', ApiManager::class, ['namespace' => $namespace]); }
	public function clearPipeline(){ $this->pipeline = []; }
	public function pipe($responderClass, $arguments = []){ $this->pipeline[] = ['responderClass' => $responderClass, 'arguments' => $arguments,]; }

	protected function route($method, $patterns, $responderClass = null, $arguments = []): Pipeline{
		if ($method === '*' || $this->request->isMethod($method)){
			$uri = rtrim($this->request->getPathInfo(), '/');
			if (!$uri) $uri = '/';
			if (!is_array($patterns)) $patterns = [$patterns];
			foreach ($patterns as $pattern) if ($this->testPattern($pattern, $uri)){
				$pipeline = new Pipeline($this->pipeline, $this->getParams($pattern, $uri), $this->request);
				if (!is_null($responderClass)) $pipeline->pipe($responderClass, $arguments);
				return $pipeline;
			}
		}
		return new DummyPipeline();
	}

	protected function testPattern($pattern, $uri){
		$pattern = preg_replace('/{.*?}/', '*', $pattern);
		return $pattern[0] == '/' ? fnmatch($pattern, $uri) : preg_match($pattern, $uri);
	}

	protected function getParams($pattern, $uri){
		if (preg_match_all('/{(.*?)}/', $pattern, $keys)){
			$keys = $keys[1];
			$valuepattern = '@^' . preg_replace('/{.*?}/', '(.*?)', $pattern) . '$@';
			preg_match($valuepattern, $uri, $values);
			array_shift($values);
			$values = array_map(function ($value){ return urldecode($value); }, $values);
			return array_combine($keys, $values);
		}else{
			return [];
		}
	}

}
