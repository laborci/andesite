<?php namespace Andesite\Attachment;

use Andesite\Attachment\Interfaces\AttachmentOwnerInterface;
use Andesite\DBAccess\Connection\Filter\Filter;
use Andesite\Mission\Web\Routing\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;


/**
 * @property-read string                                   $path
 * @property-read string                                   $url
 * @property-read array                                    $thumbnailConfig
 * @property-read Category[]                               $categories
 * @property-read \Andesite\DBAccess\Connection\Repository $dbRepository
 */
class Storage{

	private \Andesite\DBAccess\Connection\Repository $dbRepository;
	private string $path;
	private string $url;
	private array $thumbnailConfig;
	private array $categories = [];

	/**
	 * @param string                                   $path
	 * @param \Andesite\DBAccess\Connection\Repository $dbRepository
	 * @param string                                   $url
	 * @param array                                    $thumbnailConfig
	 * @param \Andesite\Attachment\Category[]          $categories
	 */
	public function __construct(string $path, \Andesite\DBAccess\Connection\Repository $dbRepository, string $url, array $thumbnailConfig){
		$this->dbRepository = $dbRepository;
		$this->path = $path;
		$this->url = $url;
		$this->thumbnailConfig = $thumbnailConfig;
		$this->thumbnailConfig['source-path'] = $this->path;
	}

	public function __get(string $key){ if (property_exists($this, $key)) return $this->$key; }

	public function addCategory(Category $category){ $this->categories[$category->name] = $category; }

	public function createCollection(AttachmentOwnerInterface $owner, string $category): Collection{ return new Collection($owner, $this, $this->categories[$category]); }

	public function initialize(){

		if (!is_dir($this->path)) mkdir($this->path, 0777, true);

		$this->dbRepository->getConnection()->exec("
			CREATE TABLE IF NOT EXISTS `" . $this->dbRepository->getTable() . "` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`ownerId` int(11) unsigned NOT NULL,
			`category` varchar(16) NOT NULL DEFAULT '',
			`filename` varchar(255) NOT NULL DEFAULT '',
			`sequence` int(11) unsigned NOT NULL,
			`meta` text NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `unique` (`filename`,`category`,`ownerId`),
			KEY `search` (`ownerId`,`category`,`sequence`)
			) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
		");
	}

}