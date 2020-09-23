<?php namespace Andesite\Attachment;

use Andesite\Attachment\Storage\FileStorage;
use Andesite\Attachment\Thumbnail\ThumbnailResponder;
use Andesite\DBAccess\Connection\PDOConnection;
use Andesite\DBAccess\ConnectionFactory;


/*
    path ~: path.data + "attachment/"
    database:
		name: default
		table_prefix: _file_
    url: /~fs
    thumbnail:
      path ~: path.var + "thumbnail/"
      secret: ferengi
      url: /~thumbnail
 */

class Repository{

	private array $config;
	private array $storages = [];
	private string $path;
	private string $databaseTablePrefix;
	private PDOConnection $database;

	public function __construct(array $config){
		$this->path = $config['path'];
		$this->database = ConnectionFactory::Module()->get($config['database']['name']);
		$this->databaseTablePrefix = $config['database']['table-prefix'];
		$this->url = $config['url'];
		$this->thumbnailConfig = $config['thumbnail'];
		$this->thumbnailConfig['source'] = $config['path'];
	}

	/**
	 * @param string $name
	 * @return \Andesite\Attachment\Storage
	 */
	public function createStorage(string $name){
		return new Storage(
			$this->path.$name.'/',
			$this->database->createRepository($this->databaseTablePrefix.$name),
			$this->url.$name.'/',
			$this->thumbnailConfig,
		);
	}

	public function routeThumbnails($router){
		$router->get($this->thumbnailConfig['url'].'/*', ThumbnailResponder::class, ['config'=>$this->thumbnailConfig])();
	}
}