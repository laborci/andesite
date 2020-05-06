<?php namespace Andesite\Ghost\Validator;

use Symfony\Component\Validator\Constraint;

class Instance extends Constraint{

	public $class;

	public function __construct($class){
		$this->class = $class;
		parent::__construct();
	}
}