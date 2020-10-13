<?php namespace Andesite\Ghost;

use Andesite\DBAccess\Connection\Filter\Filter;
class Relation {

	const TYPE_HASMANY = 'hasMany';
	const TYPE_BELONGSTO = 'belongsTo';
	const TYPE_BELONGSTOMANY = 'belongsToMany';

	public $name;
	public $type;
	public $descriptor;

	public function __construct($name, $type, $descriptor) {
		$this->name = $name;
		$this->type = $type;
		$this->descriptor = $descriptor;
	}

	public function get(Ghost $object, $order=null, $limit=null, $offset = null){
		/** @var \Andesite\Ghost\Repository $targetRepository */

		/** @var Ghost $targetGhost */
		$targetGhost = $this->descriptor['ghost'];
		$targetRepository = $targetGhost::$model->repository;
		$field = $this->descriptor['field'];

		switch ($this->type){
			case self::TYPE_BELONGSTO:
				return $targetRepository->pick($object->$field);
				break;
			case self::TYPE_HASMANY:
				if($targetGhost::$model->fields[$field]->type === Field::TYPE_JSON){
					return $targetRepository->search(Filter::where('JSON_CONTAINS(`'.$field.'`, $1, "$")', $object->id))->orderIf(!is_null($order), $order)->collect($limit, intval($offset));
				}else{
					return $targetRepository->search(Filter::where($field.'=$1', $object->id))->orderIf(!is_null($order), $order)->collect($limit, intval($offset));
				}
				break;
			case self::TYPE_BELONGSTOMANY:
				return $targetRepository->collect($object->$field);
				break;
		}
		return null;
	}
}