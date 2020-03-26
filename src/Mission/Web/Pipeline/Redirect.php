<?php namespace Andesite\Mission\Web\Pipeline;

class Redirect extends Segment {

	const ARG_URL='url';

	protected $url;
	protected $status;

	static function setup($url = '/', $status = 302){
		return [static::class, compact('url','status')];
	}

	final public function __invoke($method = null) {
		$this->url = $this->getArgumentsBag()->get('url', '/');
		$this->status = $this->getArgumentsBag()->get('status', 302);

		if(!is_null($method)) $this->$method(); else $this->run();

		$response = $this->getResponse();
		$response->headers->set('Location', $this->url);
		$response->setStatusCode($this->status);
	}

	protected function run(){

	}

}