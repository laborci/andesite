<?php namespace Andesite\Ghost;

use Andesite\Attachment\AttachmentCategory;
use Andesite\Attachment\AttachmentRepository;
use Andesite\Attachment\AttachmentStorage;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\PDOConnection;
use Andesite\DBAccess\ConnectionFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

class Model{

	/** @var PDOConnection */
	public $connection;
	public $table;
	/** @var Field[] */
	public $fields = [];
	/** @var Relation[] */
	public $relations = [];
	public $ghost;
	/** @var Repository */
	public $repository;
	/** @var AttachmentStorage */
	protected $attachmentStorage;
	public $connectionName;
	/** @var array */
	public $virtuals = [];
	/** @var array */
	public $getters = [];
	/** @var array */
	public $setters = [];
	/** @var bool */
	protected $mutable = true;

	private $validators = [];
	final public function addValidator($field, Constraint $validator){
		if (!array_key_exists($field, $this->validators)) $this->validators[$field] = [];
		$this->validators[$field][] = $validator;
	}

	public function validate(Ghost $item, $onlymessage = true){
		$validator = Validation::createValidator();
		$errors = [];
		foreach ($this->validators as $field => $validators){
			$violations = $validator->validate($item->$field, $validators);
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

	public function __construct($ghost){
		$table = $ghost::Table;
		$connectionName = $ghost::ConnectionName;
		$this->connection = ConnectionFactory::Module()->get($connectionName);
		$this->table = $table;
		$this->ghost = $ghost;
		$this->repository = new Repository($ghost, $this);
		$this->connectionName = $connectionName;
		$this->attachmentStorage = AttachmentRepository::Module()->getStorage($this->table, GhostManager::Module(false)->getAttachment());
	}

	/**
	 * @param string           $field
	 * @param null|bool|string $getter false: no getter; null: passThrough; true: get'Field'() method; string: your method name
	 * @param bool|string      $setter false: no setter; true: set'Field'() method; string: your method name
	 */
	public function protectField($field, $getter = null, $setter = false){
		if ($getter === true) $getter = 'get' . ucfirst($field);
		if ($setter === true || $setter === null) $setter = 'set' . ucfirst($field);
		if ($getter !== false) $this->getters[$field] = ['type' => 'virtual', 'method' => $getter];
		if ($setter !== false) $this->setters[$field] = ['method' => $setter];
		$this->fields[$field]->protect($getter, $setter);
	}

	/**
	 * @param string      $field
	 * @param bool|string $getter false: no getter; true: get'Field'() method; string: your method name
	 * @param bool|string $setter false: no setter; true: set'Field'() method; string: your method name
	 */
	public function virtual($field, $getter = true, $setter = false, $type = ''){
		if ($getter === true) $getter = 'get' . ucfirst($field);
		if ($setter === true || $setter === null) $setter = 'set' . ucfirst($field);
		if ($getter !== false) $this->getters[$field] = ['type' => 'virtual', 'method' => $getter];
		if ($setter !== false) $this->setters[$field] = ['method' => $setter];
		$this->virtuals[$field] = ['setter' => $setter, 'getter' => $getter, 'name' => $field, 'type' => $type];
	}

	public function immutable(){ $this->mutable = false; }

	public function isMutable(){ return $this->mutable; }

	public function createGhost(): Ghost{ return new $this->ghost; }

	public function hasMany($target, $ghost, $field): Relation{
		$this->getters[$target] = ['type' => 'relation'];
		return $this->relations[$target] = new Relation($target, Relation::TYPE_HASMANY, ['ghost' => $ghost, 'field' => $field]);
	}

	public function belongsTo($target, $ghost, $field = null): Relation{
		if ($field === null) $field = $target . 'Id';
		$this->getters[$target] = ['type' => 'relation'];
		return $this->relations[$target] = new Relation($target, Relation::TYPE_BELONGSTO, ['ghost' => $ghost, 'field' => $field]);
	}

	public function addField($name, $type, $data = null): Field{
		return $this->fields[$name] = new Field($name, $type, $data);
	}

	public function hasAttachment($name): AttachmentCategory{
		$this->getters[$name] = ['type' => 'attachment'];
		return $this->getAttachmentStorage()->addCategory($name);
	}

	public function getAttachmentStorage(){
		return $this->attachmentStorage;
	}
}