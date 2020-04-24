<?php namespace Andesite\Attachment;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AttachmentCategory implements AttachmentCategorySetterInterface{

	private string $name;
	private array $acceptedExtensions = [];
	private int $maxFileSize = -1;
	private int $maxFileCount = -1;
	private AttachmentStorage $attachmentStorage;
	/** @var \Andesite\Attachment\AttachmentCategoryManager[] */
	private array $attachmentCategoryManagers = [];

	public function __construct(string $name, AttachmentStorage $attachmentStorage){
		$this->name = $name;
		$this->attachmentStorage = $attachmentStorage;
	}

	public function acceptExtensions(string ...$extensions): self{
		$this->acceptedExtensions = array_map(function ($ext){ return strtolower($ext); }, $extensions);
		return $this;
	}

	public function maxFileSize(int $maxFileSizeInBytes): self{
		$this->maxFileSize = $maxFileSizeInBytes;
		return $this;
	}

	public function maxFileCount(int $maxFileCount): self{
		$this->maxFileCount = $maxFileCount;
		return $this;
	}

	public function getCategoryManager(AttachmentOwnerInterface $owner): AttachmentCategoryManager{
		if (!array_key_exists($owner->getPath(), $this->attachmentCategoryManagers)){
			$this->attachmentCategoryManagers[$owner->getPath()] = new AttachmentCategoryManager($this, $owner);
		}
		return $this->attachmentCategoryManagers[$owner->getPath()];
	}

	/** @return string[] */
	public function getAcceptedExtensions(): array{ return $this->acceptedExtensions; }

	public function getMaxFileSize(): int{ return $this->maxFileSize; }

	public function getName(): string{ return $this->name; }

	public function getMaxFileCount(): int{ return $this->maxFileCount; }

	public function getAttachmentStorage(): AttachmentStorage{ return $this->attachmentStorage; }

//	public function isValidUpload(File $upload){
//		if ($this->maxFileSize!== -1 && $upload->getSize() > $this->maxFileSize)
//			return false;
//		$ext = $upload instanceof UploadedFile ? $upload->getClientOriginalExtension() : $upload->getExtension();
//		if (!is_null($this->acceptedExtensions) && !in_array($ext, $this->acceptedExtensions))
//			return false;
//		return true;
//	}

}