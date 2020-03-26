<?php namespace Andesite\Ghost;

use Andesite\Attachment\AttachmentCategoryManager;

/**
 * @mixin Ghost
 */
trait GhostAttachmentTrait {

	private $path;

	public function getPath() {
		if (is_null($this->path)) {
			$id36 = str_pad(base_convert($this->id, 10, 36), 6, '0', STR_PAD_LEFT);
			$this->path = '/' . substr($id36, 0, 2) .
				'/' . substr($id36, 2, 2) .
				'/' . substr($id36, 4, 2) . '/';
		}
		return $this->path;
	}

	public function getAttachmentCategoryManager($categoryName): AttachmentCategoryManager {
		if (!$this->isExists()) throw new \Exception('Ghost not exists yet!');
		/** @var Model $model */
		$model = static::$model;
		return $model->getAttachmentStorage()->getCategory($categoryName)->getCategoryManager($this);
	}

	/** @return \Andesite\Attachment\AttachmentCategory[] */
	public function getAttachmentCategories():array{
		/** @var Model $model */
		$model = static::$model;
		return $model->getAttachmentStorage()->getCategories();
	}

}