<?php namespace Andesite\Attachment\Interfaces;

interface CategorySetterInterface{
	public function acceptExtensions(string ...$extensions): self;
	public function maxFileSize(int $maxFileSizeInBytes): self;
	public function maxFileCount(int $maxFileCount): self;
}