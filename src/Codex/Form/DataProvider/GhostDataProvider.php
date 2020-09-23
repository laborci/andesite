<?php namespace Andesite\Codex\Form\DataProvider;

use Andesite\Core\Env\Env;
use Andesite\DBAccess\Connection\Filter\Filter;
use Exception;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Andesite\Codex\Interfaces\DataProviderInterface;
use Andesite\Codex\Interfaces\ItemDataImporterInterface;
use Andesite\Ghost\Ghost;


class GhostDataProvider implements DataProviderInterface{

	/** @var callable null */
	protected $baseFilter = null;
	public function setBaseFilter(callable $filterFunc){
		$this->baseFilter = $filterFunc;
	}
	/** @var callable null */
	protected $searchFilter = null;
	public function setSearchFilter(callable $filterFunc){
		$this->searchFilter = $filterFunc;
	}

	protected $fieldConverters = [];
	public function addFieldConverter($field, $method = null){
		if (is_null($method)) $method = $field;
		if (is_string($method)) $method = function ($item) use ($method){ return $item->$method; };
		$this->fieldConverters[$field] = $method;
	}

	protected $fieldImporters = [];
	public function addFieldImporter($method){
		$this->fieldImporters[] = $method;
	}

	protected $ghost;
	/** @var \Andesite\Ghost\Model model */
	protected $model;

	public function __construct($ghost){
		$this->ghost = $ghost;
		$this->model = $ghost::$model;
	}

	public function getList($page, $sorting, $filter, $pageSize, &$count): array{
		$finder = $this->model->repository->search($this->createFilter($filter))->orderIf(!is_null($sorting), $sorting['field'] . ' ' . $sorting['dir']);
		return $finder->collectPage($pageSize, $page, $count);
	}

	public function convertItem($item): array{
		/** @var Ghost $item */
		$data = $item->export();
		foreach ($this->fieldConverters as $field => $converter){
			$data[$field] = $converter($item);
		}
		return $data;
	}

	public function createFilter($filter = null){
		$baseFilter = $this->baseFilter ? ( $this->baseFilter )($filter) : null;
		$searchFilter = $this->searchFilter ? ( $this->searchFilter )($filter) : null;
		if (is_null($searchFilter) && is_null($baseFilter)) return null;
		return Filter::where($baseFilter)->and($searchFilter);
	}

	public function getItem($id): ?Ghost{ return $this->model->repository->pick($id); }

	public function getNewItem(): Ghost{ return new $this->model->ghost; }

	public function deleteItem($id){ return $this->model->repository->delete($id); }

	public function updateItem($id, array $data, ItemDataImporterInterface $itemDataImporter){
		/** @var Ghost $item */
		$item = $this->getItem($id);
		$item = $itemDataImporter->importItemData($item, $data);
		return $item->save();
	}

	public function createItem(array $data, ItemDataImporterInterface $itemDataImporter){
		/** @var Ghost $item */
		$item = $this->getNewItem();
		$item = $itemDataImporter->importItemData($item, $data);
		return $item->save();
	}

	public function importItemData($item, $data){
		/** @var Ghost $item */
		$item->import($data);
		foreach ($this->fieldImporters as $importer){
			$importer($item, $data);
		}
		return $item;
	}

	public function uploadAttachment($id, $category, UploadedFile $file){
		/** @var Ghost $item */
		$item = $this->getItem($id);
		$item->getAttachmentCollection($category)->addFile($file);
	}

	public function getAttachments($id): array{
		/** @var Ghost $item */
		$item = $this->getItem($id);
		$categories = $item::$model->attachmentStorage->categories;
		$collection = [];
		foreach ($categories as $category){
			$attachments = $item->getAttachmentCollection($category->name);
			$collection[$category->name] = [];
			foreach ($attachments as $attachment){
				$record = $attachment->getRecord();
				if (substr($record['mime-type'], 0, 6) === 'image/'){
					$record['thumbnail'] = in_array($record['extension'], ['png', 'gif', 'jpg', 'jpeg']) ? $attachment->thumbnail->crop(100, 100)->png : $attachment->url;
				}
				$collection[$category->name][] = $record;
			}
		}
		return $collection;
	}

	public function cropAttachment($id, $file, $category, $data){
		$item = $this->getItem($id);
		$file = $item->getAttachmentCollection($category)->get($file)->file->getRealPath();
		$imgInfo = getimagesize($file);
		$oType = $imgInfo['2'];
		switch ($oType){
			case 1:
				$img = imagecreatefromgif($file);
				break;
			case 2:
				$img = imagecreatefromjpeg($file);
				break;
			case 3:
				$img = imagecreatefrompng($file);
				break;
			default:
				throw new Exception('unsupported file');
		}

		$width = $data['width'];
		$height = $data['height'];
		$x = $data['x'];
		$y = $data['y'];

		$newImg = imageCreateTrueColor($width, $height);
		imagefill($newImg, 0, 0, imagecolorallocatealpha($newImg, 0, 0, 0, 127));

		imagecopyresampled($newImg, $img, 0, 0, $x, $y, $width, $height, $width, $height);
		imagedestroy($img);

		$output = Env::Service()->get('path.tmp') . '/' . uniqid() . '.png';
		ImagePng($newImg, $output);
		$item->getAttachmentCollection($category)->addFile(new File($output));
		unlink($output);

	}

	public function copyAttachment($id, $file, $source, $target){
		$item = $this->getItem($id);
		$item->getAttachmentCollection($target)->addFile($item->getAttachmentCollection($source)->get($file)->file);
	}
	public function moveAttachment($id, $file, $source, $target){
		$item = $this->getItem($id);
		$item->getAttachmentCollection($target)->addFile($item->getAttachmentCollection($source)->get($file)->file);
		$item->getAttachmentCollection($source)->get($file)->delete();
	}

	public function deleteAttachment($id, $file, $category){
		$item = $this->getItem($id);
		$item->getAttachmentCollection($category)->get($file)->delete();
	}
	public function renameAttachment($id, $file, $category, $newname){
		$item = $this->getItem($id);
		$item->getAttachmentCollection($category)->get($file)->rename($newname);
	}

	public function reorderAttachment($id, $file, $category, $sequence){
		$item = $this->getItem($id);
		$item->getAttachmentCollection($category)->get($file)->setSequence($sequence);
	}

}

