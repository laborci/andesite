<?php namespace Andesite\Mission\Web\Responder;

use Andesite\Mission\Web\Pipeline\Responder;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class JsonResponder extends Responder {

	public function __invoke($method = 'respond') {
		if (method_exists($this, 'shutDown')) {
			register_shutdown_function([$this, 'shutDown']);
		}
		$response = $this->getResponse();
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($this->$method(...array_values($this->getArgumentsBag()->all())), JSON_UNESCAPED_UNICODE));
		$this->next();
	}

	protected function respond() { return null; }

	final protected function getJsonPayload(): array { return json_decode($this->getRequest()->getContent(), true); }

	final protected function getJsonParamBag(): ParameterBag {
		$data = json_decode($this->getRequest()->getContent(), true);
		return new ParameterBag( is_array($data) ? $data : []);
	}

	protected function error($error){
		$this->getResponse()->setStatusCode(400);
		return ['error' => $error];
	}
}