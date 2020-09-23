<?php namespace Andesite\Ghost;

use Andesite\Attachment\Attachment;
use Andesite\Attachment\AttachmentCategoryManager;
use Andesite\Attachment\Collection;


/**
 * @mixin Ghost
 */
trait GhostAttachmentTrait {

	private $path;

	public function getId(): int{return $this->id;}
	public function getAttachmentCollection($name):?Collection{
		return array_key_exists($name, $this->attachmentCollections) ?
			$this->attachmentCollections[$name] :
			$this->attachmentCollections[$name] = static::$model->attachmentStorage->createCollection($this, $name);
	}


//	public function getAttachmentCategoryManager($categoryName): AttachmentCategoryManager {
//		if (!$this->isExists()) throw new \Exception('Ghost not exists yet!');
//		/** @var Model $model */
//		$model = static::$model;
//		return $model->getAttachmentStorage()->getCategory($categoryName)->getCategoryManager($this);
//	}
//
//	/** @return \Andesite\Attachment\AttachmentCategory[] */
//	public function getAttachmentCategories():array{
//		/** @var Model $model */
//		$model = static::$model;
//		return $model->getAttachmentStorage()->getCategories();
//	}

}