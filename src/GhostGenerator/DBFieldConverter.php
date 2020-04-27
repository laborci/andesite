<?php namespace Andesite\GhostGenerator;

use Andesite\Ghost\Field;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class DBFieldConverter{

	public $name;
	public $type;
	public $maxlength;
	public $nullable;
	public $default;
	public $fieldType;
	public $options = null;
	public $phpType;
	public $descriptor;

	public function __construct($descriptor){
		$this->descriptor = $descriptor;
		$this->name = $descriptor['COLUMN_NAME'];
		$this->type = $descriptor['DATA_TYPE'];
		$this->maxlength = $descriptor['CHARACTER_MAXIMUM_LENGTH'];
		$this->nullable = $descriptor['IS_NULLABLE'] === 'YES';
		$this->default = $descriptor['COLUMN_DEFAULT'];
		$this->primary = strpos($descriptor['COLUMN_KEY'], 'PRI') !== false;

		$this->fieldType = $this->getFieldType($descriptor);
		$this->phpType = $this->getPhpType();
		if ($this->fieldType === Field::TYPE_ENUM || $this->fieldType === Field::TYPE_SET){
			preg_match_all("/'(.*?)'/", $descriptor['COLUMN_TYPE'], $matches);
			$this->options = $matches[1];
		}
		$this->validators = $this->getValidators();
	}

	public function getValidators(){

		$validators = [];

		if (!$this->nullable && !$this->primary) $validators[] = [NotNull::class];

		switch ($this->fieldType){
			case Field::TYPE_BOOL:
				$validators[] = [Type::class, 'bool'];
				break;
			case Field::TYPE_DATE:
				$validators[] = [Type::class, 'object'];
				break;
			case Field::TYPE_DATETIME:
				$validators[] = [Type::class, 'object'];
				break;
			case Field::TYPE_STRING:
				$validators[] = [Type::class, 'string'];
				$validators[] = [Length::class, ['max' => $this->maxlength]];
				break;
			case Field::TYPE_ENUM:
				$validators[] = [Type::class, 'string'];
				$validators[] = [Choice::class, $this->options];
				break;
			case Field::TYPE_SET:
				$validators[] = [Type::class, 'array'];
				$validators[] = [Choice::class, ['multiple' => true, 'choices' => $this->options]];
				break;
			case Field::TYPE_INT:
			case Field::TYPE_ID:
				$validators[] = [Type::class, 'int'];
				if (strpos($this->descriptor['COLUMN_TYPE'], 'unsigned') !== false){
					$validators[] = [PositiveOrZero::class];
				}
				break;
			case Field::TYPE_FLOAT:
				$validators[] = [Type::class, 'float'];
				if (strpos($this->descriptor['COLUMN_TYPE'], 'unsigned') !== false){
					$validators[] = [PositiveOrZero::class];
				}
				break;
		}
		return $validators;
	}

	public function getPhpType(){
		switch ($this->fieldType){
			case Field::TYPE_BOOL:
				return 'boolean';
			case Field::TYPE_DATE:
				return '\Valentine\Date';
			case Field::TYPE_DATETIME:
			case Field::TYPE_TIME:
				return '\DateTime';
			case Field::TYPE_ENUM:
			case Field::TYPE_STRING:
				return 'string';
			case Field::TYPE_SET:
				return 'array';
			case Field::TYPE_INT:
			case Field::TYPE_ID:
				return 'int';
			case Field::TYPE_FLOAT:
				return 'float';
		}
	}

	public function getFieldType($descriptor): string{

		if ($descriptor['COLUMN_COMMENT'] == 'json') return Field::TYPE_JSON;
		if ($descriptor['COLUMN_TYPE'] == 'tinyint(1)') return Field::TYPE_BOOL;
		if (strpos($descriptor['COLUMN_TYPE'], 'int(11) unsigned') === 0 && (
				substr($descriptor['COLUMN_NAME'], -2) == 'Id' ||
				$descriptor['COLUMN_NAME'] == 'id' ||
				$descriptor['COLUMN_COMMENT'] == 'id'
			)
		) return Field::TYPE_ID;

		switch ($descriptor['DATA_TYPE']){
			case 'date':
				return Field::TYPE_DATE;
			case 'datetime':
				return Field::TYPE_DATETIME;
			case 'float':
				return Field::TYPE_FLOAT;
			case 'int':
			case 'tinyint':
			case 'smallint':
			case 'tinyint':
			case 'mediumint':
			case 'bigint':
				return Field::TYPE_INT;
			case 'json':
				return Field::TYPE_JSON;
			case 'varchar':
			case 'char':
			case 'text':
			case 'tinytext':
			case 'mediumtext':
			case 'longtext':
				return Field::TYPE_STRING;
			case 'set':
				return Field::TYPE_SET;
			case 'enum':
				return Field::TYPE_ENUM;
			case 'time':
				return Field::TYPE_TIME;

		}
	}
}
