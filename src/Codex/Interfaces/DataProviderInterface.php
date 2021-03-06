<?php namespace Andesite\Codex\Interfaces;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface DataProviderInterface extends ItemDataImporterInterface, ItemConverterInterface, FilterCreatorInterface{

	public function getList($page, $sorting, $filter, $pageSize, &$count): array;
	public function getItem($id);
	public function getNewItem();
	public function deleteItem($id);
	public function updateItem($id, array $data, ItemDataImporterInterface $itemDataImporter);
	public function createItem(array $data, ItemDataImporterInterface $itemDataImporter);

	public function uploadAttachment($id, $category, UploadedFile $file);
	public function getAttachments($id): array;
	public function copyAttachment($id, $file, $source, $target);
	public function moveAttachment($id, $file, $source, $target);
	public function deleteAttachment($id, $file, $category);
}