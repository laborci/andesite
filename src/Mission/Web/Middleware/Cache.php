<?php namespace Andesite\Mission\Web\Middleware;

use Andesite\Core\Env\Env;
use Andesite\Util\Cache\FileCache;
use Andesite\Mission\Web\Pipeline\Middleware;
use Symfony\Component\HttpFoundation\Request;

class Cache extends Middleware {

	private $outputCachePath;
	public function __construct(){
		$this->outputCachePath = Env::Service()->get('path.cache-middleware');
	}

	public function run(){
		if($this->getRequest()->getMethod() !== Request::METHOD_GET) $this->next();
		else{
			$cache = new FileCache($this->outputCachePath);
			$cacheKey = crc32($this->getRequest()->getRequestUri());
			if($cache->isValid($cacheKey)){
				$this->setResponse(unserialize($cache->get($cacheKey)));
				$this->getResponse()->headers->set('x-cached-until', $cache->getAge($cacheKey)*-1);
			}else {
				$this->next();
				if($this->getRequest()->attributes->getBoolean('cache', false)){
					$cacheInterval = $this->getRequest()->attributes->getInt('cache-interval', 60);
					$cache->set($cacheKey, serialize($this->getResponse()), $cacheInterval);
					$this->getResponse()->headers->set('x-cache-interval', $cacheInterval);
				}
			}
		}
	}

}