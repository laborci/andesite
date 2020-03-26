<?php namespace Andesite\Codex\Form\ListHandler;

use JsonSerializable;
use Andesite\Codex\Form\AdminDescriptor;
use Andesite\Codex\Form\Field;
use Andesite\Codex\Interfaces\DataProviderInterface;
use Andesite\Codex\Interfaces\FilterCreatorInterface;
use Andesite\Codex\Interfaces\ItemConverterInterface;
class ListHandler implements JsonSerializable{

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	/** @var ListField[] */
	protected $fields = [];

	/** @var AdminDescriptor */
	protected $admin;

	/** @var DataProviderInterface */
	protected $dataProvider;

	/** @var ItemConverterInterface */
	private $itemConverter;

	/** @var FilterCreatorInterface */
	private $filterCreator;

	protected $pageSize = 50;
	protected $JSplugins = [];
	protected $sorting;
	protected $idField = "id";

	public function __construct(AdminDescriptor $admin){
		$this->admin = $admin;
		$this->dataProvider = $admin->getDataProvider();
		$this->itemConverter = $this->dataProvider;
		$this->filterCreator = $this->dataProvider;
	}

	public function setPageSize(int $pageSize){ $this->pageSize = $pageSize; }
	public function addJSPlugin(...$plugins){ foreach ($plugins as $plugin) $this->JSplugins[] = $plugin; }
	public function setIdField($field){ $this->idField = $field; }
	public function setItemConverter(ItemConverterInterface $itemConverter){ $this->itemConverter = $itemConverter; }
	public function setFilterCreator(FilterCreatorInterface $filterCreator){ $this->filterCreator = $filterCreator; }

	public function add(Field $field, $label = null): ListField{
		if (is_null($label)) $label = $field->label;
		$field = new ListField($field->name, $label);
		$this->fields[] = $field;
		return $field;
	}

	public function get($page, $sorting = null, $filter = null): ListingResult{

		$items = $this->dataProvider->getList($page, $sorting, $filter, $this->pageSize, $count);
		$rows = [];
		foreach ($items as $item) $rows[] = $this->itemConverter->convertItem($item);

		foreach ($rows as $key => $row){
			$rows[$key] = [];
			foreach ($this->fields as $field){
				if (!$field->isClientOnly()){
					if(is_array($row[$field->getName()])){
						$value = [];
						foreach ($row[$field->getName()] as $v){
							$value[] = ($dictionary = $field->getDictionary()) ? $dictionary($v) : $v;
						}
						$rows[$key][$field->getName()] = join(', ', $value);
					}
					else $rows[$key][$field->getName()] = ($dictionary = $field->getDictionary()) ? $dictionary($row[$field->getName()]) : $row[$field->getName()];
				}
			}
		}
		return new ListingResult($rows, $count, $page);
	}

	public function jsonSerialize(){
		return [
			'plugins'  => $this->JSplugins,
			'pageSize' => $this->pageSize,
			'fields'   => $this->fields,
			'idField'  => $this->idField,
		];
	}

}
