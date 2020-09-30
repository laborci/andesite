<?php namespace Andesite\Attachment\Interfaces;

use Andesite\Attachment\Attachment;
use Andesite\Attachment\Category;
use Andesite\Attachment\Collection;


interface AttachmentOwnerInterface{
	public function getId():int;
	public function onAttachmentAdded(Collection $collection, Attachment $attachment);
	public function onAttachmentRemoved(Collection $collection);
	public function getGUID(): string;
}