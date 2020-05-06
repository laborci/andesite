<?php namespace Andesite\Ghost;

use Andesite\Attachment\AttachmentCategory;
use Andesite\Attachment\AttachmentCategorySetterInterface;
use Symfony\Component\Validator\Constraint;

interface Decorator{
	public function addField($name, $type): Field;
	public function immutable();
	public function belongsTo($target, $ghost, $field = null): Relation;
	public function hasMany($target, $ghost, $field): Relation;
	public function hasAttachment($name): AttachmentCategorySetterInterface;
	public function addValidator($field, Constraint $validator);
	/**
	 * @param string           $field
	 * @param null|bool|string $getter false: no getter; null: passThrough; true: get'Field'() method; string: your method name
	 * @param bool|string      $setter false: no setter; true: set'Field'() method; string: your method name
	 */
	public function protectField($field, $getter = null, $setter = false);
	/**
	 * @param string      $field
	 * @param bool|string $getter false: no getter; true: get'Field'() method; string: your method name
	 * @param bool|string $setter false: no setter; true: set'Field'() method; string: your method name
	 */
	public function virtual($field, $getter = true, $setter = false, $type = '');
}
//class Decorator{
//
//	/**
//	 * @var Model
//	 */
//	private $model;
//	public function __construct(Model $model){ $this->model = $model; }
//
//	public function addField($name, $type): Field{ return $this->model->addField($name, $type); }
//	public function immutable(){ $this->model->immutable(); }
//	public function belongsTo($target, $ghost, $field = null): Relation{ return $this->model->belongsTo($target, $ghost, $field); }
//	public function hasMany($target, $ghost, $field): Relation{ return $this->model->hasMany($target, $ghost, $field); }
//	public function hasAttachment($name): AttachmentCategorySetterInterface{ return $this->model->hasAttachment($name); }
//	public function addValidator($field, Constraint $validator){ return $this->model->addValidator($field, $validator); }
//	/**
//	 * @param string           $field
//	 * @param null|bool|string $getter false: no getter; null: passThrough; true: get'Field'() method; string: your method name
//	 * @param bool|string      $setter false: no setter; true: set'Field'() method; string: your method name
//	 */
//	public function protectField($field, $getter = null, $setter = false){ $this->model->protectField($field, $getter, $setter); }
//	/**
//	 * @param string           $field
//	 * @param bool|string $getter false: no getter; true: get'Field'() method; string: your method name
//	 * @param bool|string      $setter false: no setter; true: set'Field'() method; string: your method name
//	 */
//	public function virtual($field, $getter = true, $setter = false, $type=''){ $this->model->virtual($field, $getter, $setter, $type); }
//}