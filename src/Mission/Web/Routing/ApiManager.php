<?php namespace Andesite\Mission\Web\Routing;

use CaseHelper\CaseHelperFactory;
use Minime\Annotations\Reader;
use Andesite\Mission\Web\Pipeline\Segment;
use Andesite\Core\ServiceManager\ServiceContainer;

class ApiManager extends Segment{

	public static function setup(Router $router, $path, $namespace){
		$router->any(rtrim($path, '/') . '/{path}', static::class, ['namespace' => $namespace])();
	}

	public function __invoke($m = null){
		$namespace = $this->getArgumentsBag()->get('namespace');
		$httpMethod = strtolower($this->getRequest()->getMethod());
		$methodCandidate = null;
		$uri = explode('/', $this->getPathBag()->get('path'));
		$params = [];
		$method = null;
		$class = null;

		try{
			do{
				$classMap = array_map(function ($item){ return CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_KEBAB_CASE)->toPascalCase($item); }, $uri);
				$classCandidate = $namespace . '\\' . join('\\', $classMap);
				$class = class_exists($classCandidate) ? $classCandidate : null;
				if (!is_null($class)) break;
				array_unshift($params, array_pop($uri));
				$methodCandidate = lcfirst(array_pop($classMap));
			}while (!empty($uri));

			if (is_null($class)) throw new Exception('', 404);

			/** @var Reader $reader */
			$reader = ServiceContainer::get(Reader::class);
			$reflection = new \ReflectionClass($class);

			$map = [];
			$onMap = [];
			$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
			foreach ($methods as $method){
				$annotations = $reader->getAnnotations($method);
				if ($annotations->get('accepts')){
					$methodName = $annotations->has('action') ? $annotations->get('action') : $method->name;
					$map[strtolower($annotations->get('accepts')).':'.$methodName] = $method;
				}elseif ($annotations->has('on')){
					$onMap[strtolower($annotations->get('on'))] = $method;
				}
			}

			if(array_key_exists($key = strtolower($httpMethod.':'.$methodCandidate), $map)){
				$method = $map[$key];
				array_shift($params);
			}elseif(array_key_exists($httpMethod, $onMap)){
				$method = $onMap[$httpMethod];
			}else{
				throw new Exception('', 404);
			}

//			if ($methodCandidate && $reflection->hasMethod($methodCandidate)){
//				$method = $reflection->getMethod($methodCandidate);
//				if ($reader->getAnnotations($method)->get('accepts') !== $httpMethod) throw new Exception('', 405);
//				array_shift($params);
//			}else{
//				$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
//				foreach ($methods as $method){
//					$method = $httpMethod === $reader->getAnnotations($method)->get('on') ? $method : null;
//					if (!is_null($method)) break;
//				}
//			}

//			if (is_null($method)) throw new Exception('', 404);
			if (count($params) < $method->getNumberOfRequiredParameters()) throw new Exception('', 400);

			$this->prepend([$class, $method->getName()], $params);

			$middlewares = array_reverse($reader->getAnnotations($method)->getAsArray('middleware'));
			foreach ($middlewares as $middleware) $this->prepend($middleware);

			$middlewares = array_reverse($reader->getAnnotations($reflection)->getAsArray('middleware'));
			foreach ($middlewares as $middleware) $this->prepend($middleware);

			$this->next();

		}catch (Exception $e){
			$this->getResponse()->setStatusCode($e->getCode());
		}
	}
}

class Exception extends \Exception{
}