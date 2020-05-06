<?php namespace Andesite\Ghost;

use Valentine\Date;

class Field{

	const TYPE_BOOL = 'BOOL';
	const TYPE_STRING = 'STRING';
	const TYPE_INT = 'INT';
	const TYPE_ID = 'ID';
	const TYPE_DATE = 'DATE';
	const TYPE_DATETIME = 'DATETIME';
	const TYPE_ENUM = 'ENUM';
	const TYPE_SET = 'SET';
	const TYPE_FLOAT = 'FLOAT';
	const TYPE_JSON = 'JSON';
	const TYPE_TIME = 'TIME';

	public $name;
	public $type;
	public $protected = false;
	public $getter = null;
	public $setter = null;
	public $options;
	public $noInsert = false;
	public $noUpdate = false;

	public function __construct($name, $type, $options = null){
		$this->name = $name;
		$this->type = $type;
		$this->options = $options;
	}

	public function __toString(){
		return $this->name;
	}

	public function protect($getter, $setter){
		$this->protected = true;
		$this->getter = $getter;
		$this->setter = $setter;
	}

	public function compose($value){
		if ($value === null) return null;
		switch ($this->type){
			case self::TYPE_DATE:
				return new Date($value);
			case self::TYPE_DATETIME:
				return new \DateTime($value);
			case self::TYPE_INT:
				return intval($value);
			case self::TYPE_ID:
				return intval($value) > 0 ? intval($value) : null;
			case self::TYPE_FLOAT:
				return floatval($value);
			case self::TYPE_BOOL:
				return (bool)$value;
			case self::TYPE_SET:
				return !$value ? [] : explode(',', $value);
			case self::TYPE_JSON:
				return json_decode($value, true);
			case self::TYPE_TIME:
				return new \DateTime($value);
		}
		return $value;
	}

	public function decompose($value){
		if ($value === null) return null;
		switch ($this->type){
			case self::TYPE_DATE:
				return ( function (Date $date){ return $date->format('Y-m-d'); } )($value);
			case self::TYPE_DATETIME:
				return ( function (\DateTime $date){ return $date->format('Y-m-d H:i:s'); } )($value);
			case self::TYPE_INT:
				return intval($value);
			case self::TYPE_ID:
				return intval($value) > 0 ? intval($value) : null;
			case self::TYPE_FLOAT:
				return floatval($value);
			case self::TYPE_BOOL:
				return (int)( (bool)$value );
			case self::TYPE_SET:
				return join(',', $value);
			case self::TYPE_JSON:
				return json_encode($value);
			case self::TYPE_TIME:
				return ( function (\DateTime $date){ return $date->format('H:i:s'); } )($value);
		}
		return $value;
	}

	public function import($value){
		if ($value === null || $this->setter === false) return null;
		switch ($this->type){
			case self::TYPE_DATE:
				return new Date($value);
			case self::TYPE_TIME:
				return new \DateTime($value);
			case self::TYPE_DATETIME:
				return \DateTime::createFromFormat(\DateTime::ISO8601, $value);
			case self::TYPE_INT:
				return intval($value);
			case self::TYPE_ID:
				return intval($value) > 0 ? intval($value) : null;
			case self::TYPE_FLOAT:
				return floatval($value);
			case self::TYPE_BOOL:
				return (bool)$value;
			case self::TYPE_SET:
				return $value;
			case self::TYPE_JSON:
				if (is_array($value)) return $value;
				return json_decode($value, true);
		}
		return $value;
	}

	public function export($value){
		if ($value === null || $this->getter === false) return null;
		switch ($this->type){
			case self::TYPE_DATE:
				return ( function (Date $date){ return $date->format(\DateTime::ISO8601); } )($value);
			case self::TYPE_TIME:
				return ( function (\DateTime $date){ return $date->format('H:i:s'); } )($value);
			case self::TYPE_DATETIME:
				return ( function (\DateTime $date){ return $date->format(\DateTime::ISO8601); } )($value);
			case self::TYPE_BOOL:
				return (bool)$value;
			case self::TYPE_INT:
				return intval($value);
			case self::TYPE_FLOAT:
				return floatval($value);
			case self::TYPE_SET:
			case self::TYPE_JSON:
				return $value;
		}
		return $value;
	}
}