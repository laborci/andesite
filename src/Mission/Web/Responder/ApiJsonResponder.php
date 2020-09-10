<?php namespace Andesite\Mission\Web\Responder;

use Minime\Annotations\Reader;
use Andesite\Core\ServiceManager\ServiceContainer;
use ReflectionClass;
use ReflectionMethod;

abstract class ApiJsonResponder extends JsonResponder{

	protected $method;

	public final function __invoke($action = 'respond'){

		$beforeExecutes = [];

		/** @var \Minime\Annotations\Reader $reader */
		$reader = ServiceContainer::get(Reader::class);
		$ref = new ReflectionClass(get_class($this));
		$methods = $ref->getMethods(ReflectionMethod::IS_PROTECTED);
		foreach ($methods as $method){
			if($reader->getMethodAnnotations($method->class, $method->name)->has('beforeExecution')){
				$beforeExecutes[] = $method->name;
			}
		}

		$response = $this->getResponse();
		$response->headers->set('Content-Type', 'application/json');

		$method = $this->getArgumentsBag()->get('method');
		$action = $this->getArgumentsBag()->getAlpha('action') ?:( method_exists($this, $method) ? $method : $action);
		$this->method = $method;

		/** @var Reader $reader */
		$reader = ServiceContainer::get(Reader::class);
		$methods = $reader->getMethodAnnotations($this, $action)->getAsArray('method');

		if(!method_exists($this, $action) || ($methods && !in_array($method, $methods))){
			$this->getResponse()->setStatusCode(404);
		}else{
			$annotations = $reader->getMethodAnnotations(get_class($this), $action);
			foreach ($beforeExecutes as $beforeExecute){
				if($this->$beforeExecute($annotations, $action) === false) return;
			}
			$response->setContent(json_encode($this->$action(...$this->getArgumentsBag()->all()), JSON_UNESCAPED_UNICODE));
			$this->next();
		}
	}

}