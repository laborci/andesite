<?php namespace Andesite\Attachment;

use Andesite\Attachment\Interfaces\AttachmentOwnerInterface;
use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\DBAccess\Connection\Repository;
use Andesite\Util\Memcache\Memcache;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * @property-read string $url
 * @property-read string $path
 * @property-read array  $thumbnailConfig
 */
class CollectionHandler{

	private Collection $collection;
	private Storage $storage;
	private Repository $repo;
	private AttachmentOwnerInterface $owner;
	private Category $category;
	private int $ownerId;
	private string $path;
	private string $url;

	private ?Memcache $memcache;
	private string $memcacheKey;

	public function __construct(Collection $collection, Storage $storage, Category $category, AttachmentOwnerInterface $owner){
		$this->collection = $collection;
		$this->storage = $storage;
		$this->category = $category;
		$this->owner = $owner;
		$this->repo = $storage->dbRepository;
		$this->thumbnailConfig = $storage->thumbnailConfig;
		$this->memcache = Memcache::Module();
		$this->memcacheKey = 'attachment/' . $this->owner->getGUID() . '/' . $this->category->name;

		$this->ownerId = $owner->getId();
		$itemPath = ( function ($id){
			$id36 = str_pad(base_convert($id, 10, 36), 6, '0', STR_PAD_LEFT);
			return substr($id36, 0, 2) .
				'/' . substr($id36, 2, 2) .
				'/' . substr($id36, 4, 2) . '/';
		} )($owner->getId());

		$this->url = $storage->url . $itemPath;
		$this->path = $storage->path . $itemPath;
	}

	public function __get(string $key){ if (property_exists($this, $key)) return $this->$key; }

	public function addFile(File $file): ?Attachment{

		if (!is_dir($this->path)) mkdir($this->path, 0777, true);

		if ($file instanceof UploadedFile){
			$file = $file->move($this->path, $file->getClientOriginalName());
		}else{
			copy($file->getRealPath(), $this->path . $file->getFilename());
			$file = new File($this->path . $file->getFilename());
		}

		$id = $this->repo->insert(
			[
				'ownerId'  => $this->ownerId,
				'filename' => $file->getFilename(),
				'category' => $this->category->name,
				'sequence' => 0,
				'meta'     => json_encode($this->createMeta()),
			], true
		);

		$this->reorder();

		$this->memcache->del($this->memcacheKey);

		return $this->get($file->getFilename());
	}

	public function removeAttachment(Attachment $attachment){
		$this->repo->delete($attachment->id);
		$count = $this->repo->search(
			Filter::where('ownerId=$1', $this->ownerId)
				->and('filename=$1', $attachment->filename)
		)->count();
		if ($count === 0) unlink($this->path . $attachment->filename);

		$this->memcache->del($this->memcacheKey);

	}

	public function get(string $filename): ?Attachment{
		return $this->createAttachment(
			$this->repo->search(
				Filter::where('category = $1', $this->category->name)
					->and('filename=$1', $filename)
					->and('ownerId=$1', $this->ownerId)
			)->pick()
		);
	}

	public function all(): array{

		$attachments = [];
		$records = $this->memcache->get($this->memcacheKey);
		if($records === false){
			$records = $this->repo->search(
				Filter::where("category=$1", $this->category->name)
					->and('ownerId=$1', $this->ownerId)
			)->order("sequence ASC")->collect();
			$this->memcache->set($this->memcacheKey, $records);
		}
		foreach ($records as $record) $attachments[] = $this->createAttachment($record);
		return $attachments;
	}

	public function setSequence(Attachment $attachment, $position){
		if ($attachment->sequence < $position) $position++;
		$this->repo->getConnection()->exec("
				UPDATE " . $this->repo->getTable() . "
				SET sequence = sequence + 1 
				WHERE 
					ownerId=" . $this->ownerId . " 
					AND category='" . $this->category->name . "'
					AND sequence >= " . $position . "
				ORDER BY sequence DESC;
				UPDATE " . $this->repo->getTable() . "
				SET sequence = " . $position . "
				WHERE id=" . $attachment->id . "
			");
		$this->reorder();
		$this->memcache->del($this->memcacheKey);
	}

	public function saveAttachment(Attachment $attachment){
		if ($attachment->id > 0){
			$this->repo->update(
				[
					'id'       => $attachment->id,
					'filename' => $attachment->filename,
					'meta'     => json_encode($attachment->meta),
				]);
		}
		$this->memcache->del($this->memcacheKey);
	}

	protected function createAttachment(?array $record): ?Attachment{
		return is_null($record) ? null : new Attachment(
			$this->collection,
			$this,
			$record['id'],
			$record['filename'],
			$record['sequence'],
			$this->createMeta(json_decode($record['meta'], true))
		);
	}

	protected function reorder(){
		$this->repo->getConnection()->exec("
				SET @sequence := 0;
				UPDATE " . $this->repo->getTable() . "
				SET sequence= @sequence := @sequence + 1 
				WHERE 
					ownerId=" . $this->ownerId . " 
					AND category='" . $this->category->name . "'
				ORDER BY sequence;
			");
	}

	protected function createMeta($data = null){
		return is_null($this->category->metaDefinition) ? null : $this->category->metaDefinition->create($data);
	}
}