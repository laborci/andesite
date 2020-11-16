<?php namespace Andesite\Magic;

class GhostFormAdapter implements FormAdapterInterface{

	/** @var \Andesite\Ghost\Ghost */
	private $ghost;
	private $export = null;
	private $attributes = null;
	private $import = null;

	public function __construct($ghost){
		$this->ghost = $ghost;
	}

	public function createBlankItem(){
		return new $this->ghost();
	}

	public function setImport(callable $import){
		$this->import = $import;
		return $this;
	}

	public function setExport(callable $export){
		$this->export = $export;
		return $this;
	}

	public function setAttributes(callable $attributes){
		$this->attributes = $attributes;
		return $this;
	}

	public function get($id = ''){
		$item = $id === '' ? $this->createBlankItem() : ( $this->ghost )::pick(intval($id));
		return [
			'id'    => $id,
			'item'  => is_null($this->export) ? $item->export() : ($this->export)($item),
			'attributes' => is_null($this->attributes) ? [] : ( $this->attributes )($item),
		];
	}

	public function save($id, $data){
		$item = $id === '' ? $item = new $this->ghost() : ( $this->ghost )::pick($id);
		if (is_null($this->import)){
			$item->import($data);
		}else{
			($this->import)($item, $data);
		}
		return $item->save();
	}

	public function delete($id){
		$id = intval($id);
		$item = ( $this->ghost )::pick($id);
		if (is_null($item)) return true;
		return $item->delete();
	}

}