<?php namespace Andesite\Ghost;

use Andesite\Ghost\Exception\ValidationError;
use JsonSerializable;
use Andesite\Attachment\AttachmentOwnerInterface;
use Andesite\Ghost\Exception\InsufficientData;
/**
 * @property-read int   id
 * @property-read Model $model
 */
abstract class Ghost implements JsonSerializable, AttachmentOwnerInterface{

	use GhostRepositoryFacadeTrait;
	use GhostAttachmentTrait;

	private $deleted;
	protected $id;

	public function isExists(): bool{ return (bool)$this->id; }

	public function isDeleted(): bool{ return $this->deleted; }

	function __toString(){ return get_called_class() . ' ' . $this->id; }

#region Model Creation

	private static function model(): ?Model{ return static::$model; }

	private static function setModel(Model $model){ return static::$model = $model; }

	public static function init(){
		if (static::model() === null){
			$model = static::createModel();
			static::setModel($model);
		}
		return static::model();
	}

	abstract static protected function createModel(): Model;

#endregion

#region Magic Methods

	public function __get(string $name){
		if (array_key_exists($name, static::model()->getters)){
			$getter = static::model()->getters[$name];
			switch ($getter['type']){
				case 'virtual':
					$method = $getter['method'];
					if ($method === null) return $this->$name;
					else return $this->$method();
					break;
				case 'relation':
					return static::model()->relations[$name]->get($this);
					break;
				case 'attachment':
					return $this->getAttachmentCategoryManager($name);
					break;
			}
		}
		return null;
	}

	public function __isset(string $name){ return array_key_exists($name, static::model()->getters); }

	public function __set($name, $value){
		if (array_key_exists($name, static::model()->setters)){
			$method = static::model()->setters[$name]['method'];
			$this->$method($value);
		}
	}

	public function __call(string $name, $arguments){
		$relation = array_key_exists($name, static::model()->relations) ? static::model()->relations[$name] : null;
		if ($relation && $relation->type === Relation::TYPE_HASMANY){
			[$order, $limit, $offset] = array_pad($arguments, 3, null);
			return $relation->get($this, $order, $limit, $offset);
		}
		return null;
	}

#endregion

#region Data Packing

	public function compose($record): Ghost{
		foreach (static::model()->fields as $fieldName => $field){
			if (array_key_exists($fieldName, $record)){
				$this->$fieldName = $field->compose($record[$fieldName]);
			}else{
				throw new InsufficientData(static::model()->table . ' ' . $fieldName);
			}
		}
		return $this;
	}

	public function decompose(){
		$record = [];
		foreach (static::model()->fields as $fieldName => $field){
			$record[$fieldName] = $field->decompose($this->$fieldName);
		}
		return $record;
	}

	public function jsonSerialize(){
		return $this->export();
	}

	public function export(){
		$record = [];
		foreach (static::model()->fields as $fieldName => $field){
			$record[$fieldName] = $field->export($this->$fieldName);
		}
		return $record;
	}

	public function import($data){
		foreach (static::model()->fields as $fieldName => $field){
			if (array_key_exists($fieldName, $data)){
				$this->$fieldName = $field->import($data[$fieldName]);
			}
		}
		return $this;
	}

#endregion

#region CRUD
	public function delete(){
		if ($this->isExists()){
			if ($this->onBeforeDelete() === false || !static::model()->isMutable()) return false;
			static::model()->repository->delete($this->id);
			$this->deleted = true;
			$this->onAfterDelete();
		}
		return true;
	}

	public function save(){
		$errors = $this->validate(false);
		if (count($errors)) throw new ValidationError($errors);
		if ($this->isExists()){
			return $this->update();
		}else{
			return $this->insert();
		}
	}

	final private function update(){
		if ($this->onBeforeUpdate() === false || !static::model()->isMutable()) return false;
		static::model()->repository->update($this);
		$this->onAfterUpdate();
		return $this->id;
	}

	final private function insert(){
		if ($this->onBeforeInsert() === false || !static::model()->isMutable()) return false;
		$this->id = static::model()->repository->insert($this);
		$this->onAfterInsert();
		return $this->id;
	}

	/**
	 * @return \Symfony\Component\Validator\ConstraintViolationList[]
	 */
	public function validate($onlymessage = true){ return static::$model->validate($this, $onlymessage); }

#endregion

#region Events
	public function onBeforeDelete(){ return true; }

	public function onAfterDelete(){ return true; }

	public function onBeforeUpdate(){ return true; }

	public function onAfterUpdate(){ return true; }

	public function onBeforeInsert(){ return true; }

	public function onAfterInsert(){ return true; }

	public function onAttachmentAdded($data = null){ return true; }

	public function onAttachmentRemoved($data = null){ return true; }
#endregion

}