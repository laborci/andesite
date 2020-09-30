<?php namespace Andesite\Ghost;

use Andesite\Attachment\AttachmentCategory;
use Andesite\Attachment\AttachmentRepository;
use Andesite\Attachment\AttachmentStorage;
use Andesite\Attachment\Category;
use Andesite\Attachment\Storage;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\PDOConnection;
use Andesite\DBAccess\ConnectionFactory;
use Andesite\Mission\Web\Routing\Exception;
use Andesite\Util\PropertyList\PropertyListDefinition;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;


/**
 * @property-read ValidatorSet               $validators
 * @property-read PDOConnection              $connection
 * @property-read bool                       $mutable
 * @property-read string                     $table
 * @property-read string                     $storage
 * @property-read string                     $ghost
 * @property-read \Andesite\Ghost\Repository $repository
 * @property-read Storage                    $attachmentStorage
 * @property-read array                      $virtuals
 * @property-read array                      $getters
 * @property-read array                      $setters
 * @property-read Field[]                    $fields
 * @property-read Relation[]                 $relations
 * @property-read string|null                $guid
 */
class Model{

	private PDOConnection $connection;
	private string $table;
	private string $ghost;
	private string $storage;
	private Repository $repository;
	private ?Storage $attachmentStorage;
	private array $virtuals = [];
	private array $getters = [];
	private array $setters = [];
	private bool $mutable = true;
	/** @var Field[] */
	private array $fields = [];
	/** @var Relation[] */
	private array $relations = [];
	/** @var \Symfony\Component\Validator\Constraint[] */
	private ValidatorSet $validators;
	private ?string $guid = null;

	public function __construct(string $ghost, string $connection, string $table, string $storage, $mutable = true){
		$this->connection = ConnectionFactory::Module()->get($connection);
		$this->table = $table;
		$this->ghost = $ghost;
		$this->mutable = $mutable;
		$this->storage = $storage;
		$this->repository = new Repository($ghost, $this);
		$this->attachmentStorage = GhostManager::Module()->getAttachmentRepository()->createStorage($storage);
		$this->validators = new ValidatorSet();
	}

	public function __get($key){ if (property_exists($this, $key)) return $this->$key; }

	final public function addValidator(string $field, Constraint $constraint): Model{
		$this->validators->addValidator($field, $constraint);
		return $this;
	}

	/**
	 * @param string           $field
	 * @param null|bool|string $getter false: no getter; null: passThrough; true: get'Field'() method; string: your method name
	 * @param bool|string      $setter false: no setter; true: set'Field'() method; string: your method name
	 */
	public function protectField($field, $getter = null, $setter = false): Model{
		if (!array_key_exists($field, $this->fields)) return $this;
		if ($getter === true) $getter = 'get' . ucfirst($field);
		if ($setter === true || $setter === null) $setter = 'set' . ucfirst($field);
		if ($getter !== false) $this->getters[$field] = ['type' => 'virtual', 'method' => $getter];
		if ($setter !== false) $this->setters[$field] = ['method' => $setter];
		$this->fields[$field]->protect($getter, $setter);
		return $this;
	}

	public function noInsertField(string $field){
		$this->fields[$field]->noInsert();
		return $this;
	}
	public function noUpdateField(string $field){
		$this->fields[$field]->noUpdate();
		return $this;
	}
	public function guid(string $field){
		if (!is_null($this->guid)) throw new \Exception('one guid field allowed');
		$this->guid = $field;
		$this->protectField($field);
		$this->noUpdateField($field);
		$this->noInsertField($field);
		return $this;
	}

	/**
	 * @param string      $field
	 * @param bool|string $getter false: no getter; true: get'Field'() method; string: your method name
	 * @param bool|string $setter false: no setter; true: set'Field'() method; string: your method name
	 */
	public function virtual($field, $getter = true, $setter = false, $type = ''): Model{
		if ($getter === true) $getter = 'get' . ucfirst($field);
		if ($setter === true || $setter === null) $setter = 'set' . ucfirst($field);
		if ($getter !== false) $this->getters[$field] = ['type' => 'virtual', 'method' => $getter];
		if ($setter !== false) $this->setters[$field] = ['method' => $setter];
		$this->virtuals[$field] = ['setter' => $setter, 'getter' => $getter, 'name' => $field, 'type' => $type];
		return $this;
	}

	public function hasMany(string $target, string $ghost, string $field): Model{
		$this->getters[$target] = ['type' => 'relation'];
		$this->relations[$target] = new Relation($target, Relation::TYPE_HASMANY, ['ghost' => $ghost, 'field' => $field]);
		return $this;
	}

	public function belongsTo(string $target, string $ghost, ?string $field = null): Model{
		if ($field === null) $field = $target . 'Id';
		$this->getters[$target] = ['type' => 'relation'];
		$this->relations[$target] = new Relation($target, Relation::TYPE_BELONGSTO, ['ghost' => $ghost, 'field' => $field]);
		return $this;
	}

	public function addField(string $name, string $type, $data = null): Model{
		$this->fields[$name] = new Field($name, $type, $data);
		return $this;
	}

	public function addAttachmentCategory(string $name, int $filesizelimit = 0, int $filelimit = 0, ?array $extensions = null, ?PropertyListDefinition $propertyList = null): Model{
		$this->getters[$name] = ['type' => 'attachment'];
		$this->attachmentStorage->addCategory(new Category($name, $extensions, $filesizelimit, $filelimit, $propertyList));
		return $this;
	}

}