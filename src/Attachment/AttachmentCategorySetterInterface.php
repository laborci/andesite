<?php namespace Andesite\Attachment;

interface AttachmentCategorySetterInterface{
	public function acceptExtensions(string ...$extensions): self;
	public function maxFileSize(int $maxFileSizeInBytes): self;
	public function maxFileCount(int $maxFileCount): self;
}