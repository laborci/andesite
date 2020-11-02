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

	/** @return Collection[] */
	public function getAttachmentCollections():array{return $this->attachmentCollections;	}

}