<?php namespace Andesite\Attachment;

use Andesite\Attachment\Thumbnail\Thumbnail;
use Andesite\Util\PropertyList\PropertyList;
use Symfony\Component\HttpFoundation\File\File;


/**
 * @property-read int $id
 * @property-read string $url
 * @property-read string $path
 * @property-read File $file
 * @property-read string $filename
 * @property-read string $category
 * @property-read int $sequence
 * @property-read Thumbnail $thumbnail
 * @property-read bool $isImage
 */
class Attachment implements \JsonSerializable{

	private int $sequence;
	private int $id;
	private ?PropertyList $meta;
	private Collection $collection;
	private CollectionHandler $handler;
	private File $file;

	public function __get($key){
		if (property_exists($this, $key)) return $this->$key;
		switch ($key){
			case 'filename':
				return $this->file->getFilename();
			case 'path':
				return $this->file->getRealPath();
			case 'url':
				return $this->handler->url . $this->file->getFilename();
			case 'thumbnail':
				return new Thumbnail($this->file, $this->handler->thumbnailConfig);
			case 'isImage':
				return str_starts_with($this->file->getMimeType(), 'image');
		}
	}
	public function __isset($name){ return property_exists($this, $name) || in_array($name, ['filename', 'path', 'url', 'thumbnail']); }

//	public function rename(string $filename){ return $this->getFilename() !== $filename ? $this->handler->renameAttachment($this, $filename) : $this; }

	public function __construct(Collection $collection, CollectionHandler $handler, int $id, string $filename, int $sequence = 0, ?PropertyList $meta = null){
		$this->file = new File($handler->path . $filename);
		$this->collection = $collection;
		$this->handler = $handler;
		$this->id = $id;
		$this->sequence = $sequence;
		$this->meta = $meta;
		$this->category = $collection->category->name;
	}

	public function setSequence(int $position){
		$this->handler->setSequence($this, $position);
		$this->collection->load();
	}

	public function get(string $key){ return is_null($this->meta) ? null : $this->meta->get($key); }

	public function set(string $key, string $value){
		if (!is_null($this->meta)){
			$this->meta->set($key, $value);
			$this->save();
		}
	}

	public function setMeta(array $meta){
		foreach ($meta as $key => $value){
			$this->meta->set($key, $value);
		}
		$this->save();
	}

	public function save(){ $this->handler->saveAttachment($this); }

	public function delete(){
		$this->collection->removeAttachment($this);
		$this->id = -1;
	}

	public function rename($name){
		if (!file_exists($this->file->getPath() . '/' . $name)){
			$this->file = $this->file->move($this->file->getPath(), $name);
			$this->save();
		}
	}

	public function jsonSerialize(): array{ return $this->getRecord(); }
	public function getRecord(): array{
		return [
			'path'      => $this->path,
			'url'       => $this->url,
			'file'      => $this->filename,
			'size'      => $this->file->getSize(),
			//'meta'      => $this->meta->get(),
			'ordinal'   => $this->sequence,
			'category'  => $this->category,
			'extension' => strtolower($this->file->getExtension()),
			'mime-type' => $this->file->getMimeType(),
		];
	}
}