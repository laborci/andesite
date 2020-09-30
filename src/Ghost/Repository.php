<?php namespace Andesite\Ghost;

use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\DBAccess\Connection\Finder;
use Andesite\Util\Cache\MemoryCache;
use Andesite\Util\Memcache\Memcache;


class Repository{

	protected $ghost;

	/** @var Model */
	protected $model;
	/** @var \Andesite\Util\Cache\MemoryCache $cache */
	protected $cache;
	/** @var \Andesite\DBAccess\Connection\Repository $dbRepository */
	protected $dbRepository;

	public function __construct($ghost, Model $model){
		$this->ghost = $ghost;
		$this->model = $model;
		$this->cache = new MemoryCache();
		$this->dbRepository = $model->connection->createRepository($model->table);
	}
	private function addToCache(Ghost $object): Ghost{
		$this->cache->add($object, $object->id);
		return $object;
	}

	public function clearCache(){
		$this->cache->clear();
	}

	public function pick($id): ?Ghost{
		if ($id === null) return null;

		$object = $this->cache->get($id);

		if (is_null($object)){
			$record = Memcache::Module()->get('ghost/' . md5($this->model->ghost . '/' . $id));
			if (!$record){
				$record = $this->dbRepository->pick($id);
				if ($record){
					Memcache::Module()->set('ghost/' . md5($this->model->ghost . '/' . $id), $record);
				}
			}
			if ($record){
				$object = $this->newGhost()->compose($record);
				$this->addToCache($object);
			}else $object = null;
		}
		return $object;
	}

	public function collect(array $ids): array{
		$objects = [];
		$ids = array_unique($ids);
		$originalIds = $ids;
		$requested = count($ids);
		if ($requested == 0) return [];

		foreach ($ids as $index => $id){
			$cached = $this->cache->get($id);
			if (!is_null($cached)){
				$objects[] = $cached;
				unset($ids[$index]);
			}
		}

		$records = [];
		$ids = array_combine($ids, $ids);
		if (count($ids)){
			$m_records = Memcache::Module()->getm(array_map(function ($id){ return 'ghost/' . md5($this->model->ghost . '/' . $id); }, $ids));
			if (is_array($m_records)){
				array_walk($m_records, function ($record) use (&$ids, &$records){
					$records[$record['id']] = $record;
					unset($ids[$record['id']]);
				});
			}
		}
		if (count($ids)){
			$db_records = $this->dbRepository->collect($ids);
			array_walk($db_records, function ($record) use(&$records){
				Memcache::Module()->set('ghost/' . md5($this->model->ghost . '/' . $record['id']), $record);
				$records[$record['id']] = $record;
			});
		}

		foreach ($records as $record){
			$object = $this->newGhost()->compose($record);
			$this->addToCache($object);
			$objects[$object->id] = $object;
		}

		$result = [];
		foreach ($originalIds as $id) if (array_key_exists($id, $objects)){
			$result[] = $objects[$id];
		}
		return $result;
	}

	protected function newGhost(): Ghost{ return new $this->ghost(); }

	protected function count(Filter $filter = null){ return $this->dbRepository->count($filter); }

	public function insert(Ghost $object){
		$record = $object->decompose();
		return $this->dbRepository->insert($record);
	}

	public function update(Ghost $object){
		$record = $object->decompose();
		Memcache::Module()->del('ghost/' . md5($this->model->ghost . '/' . $object->id));
		return $this->dbRepository->update($record);
	}

	public function delete(int $id){
		$this->cache->delete($id);
		Memcache::Module()->del('ghost/' . md5($this->model->ghost . '/' . $id));
		return $this->dbRepository->delete($id);
	}

	public function search(Filter $filter = null): Finder{
		$finder = $this->dbRepository->search($filter);
		$finder->setConverter(function ($record){
			Memcache::Module()->set('ghost/' . md5($this->model->ghost . '/' . $record['id']), $record);
			$object = $this->newGhost()->compose($record);
			return $this->addToCache($object);
		});
		return $finder;
	}
}