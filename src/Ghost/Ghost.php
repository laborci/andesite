<?php namespace Andesite\Ghost;

use Andesite\Attachment\Attachment;
use Andesite\Attachment\Collection;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Ghost\Exception\ValidationError;
use Andesite\Util\Memcache\Memcache;
use JsonSerializable;
use Andesite\Attachment\Interfaces\AttachmentOwnerInterface;
use Andesite\Ghost\Exception\InsufficientData;
use Minime\Annotations\Reader;
use Rah\Danpu\Dump;
use Symfony\Component\Validator\Validation;


/**
 * @property-read int        id
 * @property-read Model|null $model
 */
abstract class Ghost implements JsonSerializable, AttachmentOwnerInterface{

	use GhostRepositoryFacadeTrait;
	use GhostAttachmentTrait;

	private $deleted;
	private $attachmentCollections = [];
	protected ?int $id = null;

	public function getUID():string { return md5(get_called_class().'\\'.$this->id); }

	public function isExists(): bool{ return (bool)$this->id; }
	public function isDeleted(): bool{ return $this->deleted; }
	function __toString(){ return get_called_class() . ' ' . $this->id; }

	static final public function init(){
		/** @var Reader $reader */
		$reader = ServiceContainer::get(Reader::class);
		$annotations = $reader->getClassAnnotations(get_called_class());

		if (is_null(static::$model)){
			static::$model = new Model(get_called_class(),$annotations->get('database'), $table = $annotations->get('table'), $annotations->get('storage', $table), $annotations->get('mutable'));
			static::model(static::$model);
			static::extendModel(static::$model);
		}
	}

	abstract static protected function extendModel(Model $model):Model;
	abstract static protected function model(Model $model):Model;

#region Magic Methods

	public function __get(string $name){
		if (array_key_exists($name, static::$model->getters)){
			$getter = static::$model->getters[$name];
			switch ($getter['type']){
				case 'virtual':
					$method = $getter['method'];
					if ($method === null) return $this->$name;
					else return $this->$method();
					break;
				case 'relation':
					return static::$model->relations[$name]->get($this);
					break;
				case 'attachment':
					return
						$this->getAttachmentCollection($name);
					break;
			}
		}
		return null;
	}

	public function __isset(string $name):bool{ return array_key_exists($name, static::$model->getters); }

	public function __set($name, $value){
		if (array_key_exists($name, static::$model->setters)){
			$method = static::$model->setters[$name]['method'];
			$this->$method($value);
		}
	}

	public function __call(string $name, $arguments){
		$relation = array_key_exists($name, static::$model->relations) ? static::$model->relations[$name] : null;
		if ($relation && $relation->type === Relation::TYPE_HASMANY){
			[$order, $limit, $offset] = array_pad($arguments, 3, null);
			return $relation->get($this, $order, $limit, $offset);
		}
		return null;
	}

#endregion

#region Data Packing

	public function compose($record, $ignore = false): Ghost{
		foreach (static::$model->fields as $fieldName => $field){
			if (array_key_exists($fieldName, $record)){
				$this->$fieldName = $field->compose($record[$fieldName]);
			}elseif (!$ignore){
				throw new InsufficientData(static::$model->table . ' ' . $fieldName);
			}
		}
		return $this;
	}

	public function decompose(){
		$record = [];
		foreach (static::$model->fields as $fieldName => $field){
			$record[$fieldName] = $field->decompose($this->$fieldName);
		}
		return $record;
	}

	public function jsonSerialize(){
		return $this->export();
	}

	public function export(){
		$record = [];
		foreach (static::$model->fields as $fieldName => $field){
			$record[$fieldName] = $field->export($this->$fieldName);
		}
		return $record;
	}

	public function import($data){
		foreach (static::$model->fields as $fieldName => $field){
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
			if ($this->onBeforeDelete() === false || !static::$model->mutable) return false;
			static::$model->repository->delete($this->id);
			$this->deleted = true;
			$this->onAfterDelete();
		}
		return true;
	}

	public function save(){
		if (!static::$model->mutable) return false;
		if ($this->onBeforeSave() === false) return false;
		$id = $this->isExists() ? $this->update() : $this->insert();
		$this->onAfterSave();
		return $id;
	}

	final private function update(){
		if ($this->onBeforeUpdate() === false) return false;
		$errors = $this->validate(false);
		if (count($errors)) throw new ValidationError($errors);
		static::$model->repository->update($this);
		$this->onAfterUpdate();
		return $this->id;
	}

	final private function insert(){
		if ($this->onBeforeInsert() === false) return false;
		$errors = $this->validate(false);
		if (count($errors)) throw new ValidationError($errors);
		$this->id = static::$model->repository->insert($this);
		$this->onAfterInsert();
		return $this->id;
	}

	protected function validators():?ValidatorSet{return null;}

	/**
	 * @return \Symfony\Component\Validator\ConstraintViolationList[]
	 */
	public function validate($onlymessage = true){
		$constraints = static::$model->validators->getConstraints();
		if(!is_null($custom = $this->validators())) $constraints= array_merge_recursive($constraints, $custom->getConstraints());
		$validator = Validation::createValidator();
		$errors = [];
		foreach ($constraints as $field => $constraints){
			$violations = $validator->validate($this->$field, $constraints);
			for ($i = 0; $i < $violations->count(); $i++){
				$error = [
					'field'   => $field,
					'message' => $violations->get($i)->getMessage(),
				];
				if (!$onlymessage) $error['violation'] = $violations->get($i);
				$errors[] = $error;
			}
		}
		return $errors;
	}

	static protected function createModel(string $class, string $connection, string $table, bool $mutable = false):Model{return (static::$model = new Model($class, $connection, $table, $mutable));}

#endregion

#region Events
	public function onBeforeDelete(){ return true; }

	public function onAfterDelete(){ return true; }

	public function onBeforeSave(){ return true; }

	public function onAfterSave(){ return true; }

	public function onBeforeUpdate(){ return true; }

	public function onAfterUpdate(){ return true; }

	public function onBeforeInsert(){ return true; }

	public function onAfterInsert(){ return true; }

	public function onAttachmentAdded(Collection $collection, Attachment $attachment){ }

	public function onAttachmentRemoved(Collection $collection){ }
#endregion

}