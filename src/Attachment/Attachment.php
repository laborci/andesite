<?php namespace Andesite\Attachment;

use Symfony\Component\HttpFoundation\File\File;

/**
 * @property-read string    $url
 * @property-read string    $path
 * @property-read string    $category
 * @property-read Thumbnail $thumbnail
 */
class Attachment extends File implements \JsonSerializable{

	public string $description;
	public int $ordinal;
	public array $meta;
	private AttachmentCategoryManager $categoryManager;

	public function __construct(
		string $filename,
		AttachmentCategoryManager $categoryManager,
		string $description = '',
		int $ordinal = 0,
		array $meta = []
	){
		parent::__construct($categoryManager->getPath() . '/' . $filename);
		$this->categoryManager = $categoryManager;
		$this->description = $description;
		$this->ordinal = $ordinal;
		$this->meta = $meta;
	}

	public function __get($name){
		switch ($name){
			case 'path':
				return $this->categoryManager->getPath() . $this->getFilename();
			case 'url':
				return $this->categoryManager->getUrl() . $this->getFilename();
			case 'category':
				return $this->getCategory()->getName();
			case 'thumbnail':
				return new Thumbnail($this, $this->categoryManager->getStorage()->getThumbnailConfig());
			case 'file':
				return $this->getFilename();
			case 'extension':
				return strtolower($this->getExtension());
		}
		return null;
	}

	public function getCategory(): AttachmentCategory{ return $this->categoryManager->getCategory(); }

	public function __isset($name){
		return in_array($name, ['path', 'url', 'category', 'thumbnail', 'file', 'extension']);
	}

	public function store(){ $this->categoryManager->store($this); }

	public function remove(){ $this->categoryManager->remove($this); }

	public function jsonSerialize(): array{ return $this->getRecord(); }

	public function getRecord(): array{
		return [
			'path'        => $this->categoryManager->getOwner()->getPath(),
			'url'         => $this->url,
			'file'        => $this->getFilename(),
			'size'        => $this->getSize(),
			'meta'        => $this->meta,
			'description' => $this->description,
			'ordinal'     => $this->ordinal,
			'category'    => $this->categoryManager->getCategory()->getName(),
			'extension'   => strtolower($this->getExtension()),
			'mime-type'   => $this->getMimeType(),
		];
	}
}