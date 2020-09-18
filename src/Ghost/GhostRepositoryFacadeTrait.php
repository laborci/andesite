<?php namespace Andesite\Ghost;

use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\DBAccess\Connection\Finder;

/**
 * @mixin Ghost
 */
trait GhostRepositoryFacadeTrait{

	static final public function clearCache(){
		return static::$model->repository->clearCache();
	}

	/** @return self */
	static final public function pick($id): ?self{
		return static::$model->repository->pick($id);
	}

	/** @return self[] */
	static final public function collect($ids): array{
		return static::$model->repository->collect($ids);
	}

	static final public function search(Filter $filter = null): Finder{
		return static::$model->repository->search($filter);
	}
}