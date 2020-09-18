<?php namespace Andesite\Attachment;

use Andesite\Attachment\Exception\CategoryNotFound;
use SQLite3;

class AttachmentStorage{

	/** @var AttachmentCategory[] */
	private $categories = [];

	/** @var SQLite3 */
	private $metaDBConnection;

	private $path;
	private $url;
	private $metaFile;
	private $storage;
	private $basePath;
	private $baseUrl;
	private $thumbnailConfig;

	public function getThumbnailConfig():array {return $this->thumbnailConfig;}

	public function __construct(string $storage, array $config){
		$this->basePath = $config['path'];
		$this->baseUrl = $config['url'];
		$this->path = $this->basePath . '/' . $storage;
		$this->url = $this->baseUrl . '/' . $storage;
		$this->metaFile = $config['meta-db-path'] . '/' . $storage . '.sqlite';
		$this->thumbnailConfig = $config['thumbnail'];
		$this->thumbnailConfig['source-path'] = $this->basePath;
		$this->storage = $storage;
	}

	public function addCategory(string $name):AttachmentCategory{
		$category = new AttachmentCategory($name, $this);
		$this->categories[$category->getName()] = $category;
		return $category;
	}

	public function getBasePath():string { return $this->basePath; }
	public function getBaseUrl():string { return $this->baseUrl; }
	public function getPath():string { return $this->path; }
	public function getUrl():string { return $this->url; }

	/** @return \Andesite\Attachment\AttachmentCategory[] */
	public function getCategories():array { return $this->categories; }
	public function getStorageName():string { return $this->storage; }
	public function hasCategory(string $category):bool { return array_key_exists($category, $this->categories); }
	public function getCategory(string $category): AttachmentCategory{
		if (array_key_exists($category, $this->categories))
			return $this->categories[$category];
		else throw new CategoryNotFound();
	}

	public function getMetaDBConnection(): SQLite3{
		if (is_null($this->metaDBConnection)){
			if (!file_exists($this->metaFile)){
				$connection = new SQLite3($this->metaFile);
				$connection->busyTimeout(5000);
				$connection->exec('PRAGMA journal_mode = wal;');
				$connection->exec("
						begin;
						create table file
						(
							path text,
							file text,
							size int,
							category text,
							description text,
							ordinal int,
							meta text,
							constraint file_pk
								primary key (path, file, category)
						);
						create index path_index on file (path);
						commit;");
				$connection->close();
			}
			$connection = new SQLite3($this->metaFile);
			$connection->busyTimeout(5000);
			$connection->exec('PRAGMA journal_mode = wal;');
			$this->metaDBConnection = $connection;
		}
		$connection->busyTimeout(5000);
		return $this->metaDBConnection;
	}


}