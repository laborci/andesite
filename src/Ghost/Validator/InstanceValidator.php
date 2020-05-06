<?php namespace Andesite\Ghost\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ValidatorException;

class InstanceValidator extends ConstraintValidator{
	public function validate($value, Constraint $constraint){
		if(is_null($value)) return;
		if (!$constraint instanceof Instance) throw new UnexpectedTypeException($constraint, Instance::class);
		if (!$value instanceof $constraint->class) throw new ValidatorException("Value should be a " . $constraint->class . " object!");
	}
}