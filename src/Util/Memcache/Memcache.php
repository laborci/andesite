<?php namespace Andesite\Util\Memcache;

use Andesite\Core\Module\Module;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Util\Alert\AlertInterface;
use Andesite\Util\Alert\TelegramAlert;
use Memcached;


class Memcache extends Module{

	protected ?Memcached $memcache = null;

	public function run($config){
		if ($config['allowed'] && class_exists(Memcached::class)){
			$this->memcache = new Memcached();
			$this->memcache->addServer($config['server']['host'], $config['server']['port']);
			$this->memcache->setOption(Memcached::OPT_PREFIX_KEY, $config['prefix']);
		}
	}

	public function get(string $key){ return is_null($this->memcache) ? false : $this->memcache->get($key); }
	public function del(string $key){ return is_null($this->memcache) ? false : $this->memcache->delete($key); }
	public function set(string $key, $data, $expiration = 0){ return is_null($this->memcache) ? false : $this->memcache->set($key, $data, $expiration); }
	public function getm(array $keys){
		if(is_null($this->memcache)){
			$diff = $keys;
			return [];
		}
		return $this->memcache->getMulti($keys);
	}
	public function setm(array $items, $expiration = 0){ return is_null($this->memcache) ? false : $this->memcache->setMulti($items, $expiration); }
}