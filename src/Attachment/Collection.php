<?php namespace Andesite\Attachment;

use Andesite\Attachment\Interfaces\AttachmentOwnerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * @property-read \Andesite\Attachment\Attachment|null $first
 * @property-read \Andesite\Attachment\Category        $category
 * @property-read int                                  $count
 * @property-read \Andesite\Attachment\Attachment[]    $array
 */
class Collection implements \IteratorAggregate, \ArrayAccess, \Countable{

	/** @var \Andesite\Attachment\Attachment[] */
	private ?array $attachments = null;
	private bool $loaded = false;
	private CollectionHandler $handler;
	private Category $category;
	private AttachmentOwnerInterface $owner;

	public function __construct(AttachmentOwnerInterface $owner, Storage $storage, Category $category){
		$this->category = $category;
		$this->owner = $owner;
		$this->handler = new CollectionHandler($this, $storage, $category, $owner);
	}

	public function addFile(File $file): ?Attachment{
		$attachment = $this->handler->addFile($file);
		$this->owner->onAttachmentAdded($this, $attachment);
		$this->load();
		return $attachment;
	}

	public function removeAttachment(Attachment $attachment){
		$this->handler->removeAttachment($attachment);
		$this->owner->onAttachmentRemoved($this);
	}

	// Loading collection

	protected function lazyLoad(): bool{ return ( !is_null($this->attachments) ?: $this->load() ) && false; }
	public function load(){ $this->attachments = $this->handler->all($this); }

	protected function getFirstAttachment(): ?Attachment{ return $this->lazyLoad() ?: ( $this->count() ? $this->attachments[0] : null ); }
	public function get($filename){
		$this->lazyLoad();
		foreach ($this->attachments as $attachment){
			if($attachment->filename === $filename) return $attachment;
		}
		return null;
	}
	// Getters

	public function __isset($name){ return array_key_exists($name, ['array', 'count', 'first', 'category']); }
	public function __get($key){
		switch ($key){
			case 'array':
				return $this->attachments;
			case 'count':
				return $this->count();
			case 'first':
				return $this->getFirstAttachment();
			case 'category':
				return $this->category;
		}
	}

	function __toString():string{ return $this->category->name; }

	// Array Behaviour

	// IteratorAggregate
	public function getIterator(): AttachmentIterator{ return $this->lazyLoad() ?: new AttachmentIterator($this->attachments); }

	// Countable
	public function count(){ return $this->lazyLoad() ?: count($this->attachments); }

	// ArrayAccess
	public function offsetGet($offset): Attachment{
		if(is_numeric($offset)) return $this->lazyLoad() ?: $this->attachments[$offset];
		return $this->__get($offset);
	}
	public function offsetExists($offset){
		if(is_numeric($offset)) return $this->lazyLoad() ?:  array_key_exists($offset, $this->attachments);
		return $this->__isset($offset);
	}
	/** @deprecated OUT OF ORDER */
	public function offsetSet($offset, $value){ }
	/** @deprecated OUT OF ORDER */
	public function offsetUnset($offset){ }

}

