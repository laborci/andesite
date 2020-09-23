<?php namespace Andesite\Attachment;

use Andesite\Attachment\Interfaces\CategorySetterInterface;
use Andesite\Util\PropertyList\PropertyList;
use Andesite\Util\PropertyList\PropertyListDefinition;
use Symfony\Component\HttpFoundation\File\File;


/**
 * @property-read string                 $name
 * @property-read array                  $acceptedExtensions
 * @property-read PropertyListDefinition $metaDefinition
 * @property-read int                    $maxFileSize
 * @property-read int                    $maxFileCount
 */
class Category{

	private string $name;
	private array $acceptedExtensions;
	private ?PropertyListDefinition $metaDefinition;
	private int $maxFileSize;
	private int $maxFileCount;

	public function __construct(string $name, ?array $acceptedExtensions = [], int $maxFileSize = 0, int $maxFileCount = 0, PropertyListDefinition $metaDefinition = null){
		$this->name = $name;
		$this->acceptedExtensions = $acceptedExtensions;
		$this->maxFileCount = $maxFileCount;
		$this->maxFileSize = $maxFileSize;
		$this->metaDefinition = $metaDefinition;
	}

	public function __get(string $key){ if (property_exists($this, $key)) return $this->$key; }

}