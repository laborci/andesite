<?php namespace Andesite\DBAccess\Connection\Filter;

use Andesite\DBAccess\Connection\PDOConnection;

class Comparison{

	/** @var string */
	protected $field;
	protected $value;
	protected $operator = null;
	protected $quote = true;

	const OPERATOR_IS = 'is';
	const OPERATOR_IS_NULL = 'is_null';
	const OPERATOR_IS_NOT_NULL = 'is_not_null';
	const OPERATOR_NOT_EQUAL = 'not_equal';
	const OPERATOR_IN = 'in';
	const OPERATOR_NOT_IN = 'not_in';
	const OPERATOR_IN_STRING = 'instring';
	const OPERATOR_LIKE = 'like';
	const OPERATOR_REV_LIKE = 'revlike';
	const OPERATOR_GLOB = 'glob';
	const OPERATOR_REV_GLOB = 'revglob';
	const OPERATOR_STARTS = 'starts';
	const OPERATOR_ENDS = 'ends';
	const OPERATOR_BETWEEN = 'between';
	const OPERATOR_REGEX = 'regex';
	const OPERATOR_GT = 'gt';
	const OPERATOR_GTE = 'gte';
	const OPERATOR_LT = 'lt';
	const OPERATOR_LTE = 'lte';

	public function __construct(string $field){
		$this->field = $field;
	}

	public function __toString(){return $this->field;}

	protected function quoteValue($value, PDOConnection $connection, $addQuotationMarks = true){ return $this->quote ? $connection->quoteValue($value, $addQuotationMarks) : $value; }
	protected function quoteArray($value, PDOConnection $connection, $addQuotationMarks = true){ return $this->quote ? $connection->quoteArray($value, $addQuotationMarks) : $value; }

	public function getSql(PDOConnection $connection){
		$sql = '';
		$field = $connection->escapeSQLEntity($this->field);

		switch ($this->operator){
			case self::OPERATOR_IS:
				$sql = "${field} = {$this->quoteValue($this->value, $connection)}";
				break;
			case self::OPERATOR_GT:
				$sql = "${field} > {$this->quoteValue($this->value, $connection)}";
				break;
			case self::OPERATOR_GTE:
				$sql = "${field} >= {$this->quoteValue($this->value, $connection)}";
				break;
			case self::OPERATOR_LT:
				$sql = "${field} < {$this->quoteValue($this->value, $connection)}";
				break;
			case self::OPERATOR_LTE:
				$sql = "${field} <= {$this->quoteValue($this->value, $connection)}";
				break;
			case self::OPERATOR_IS_NULL:
				$sql = "${field} IS NULL";
				break;
			case self::OPERATOR_IS_NOT_NULL:
				$sql = "${field} IS NOT NULL";
				break;
			case self::OPERATOR_NOT_EQUAL:
				$sql = "${field} != {$this->quoteValue($this->value, $connection)}";
				break;
			case self::OPERATOR_NOT_IN:
				$sql = (empty($this->value) ? "" : "${field} NOT IN (" . join(',', $this->quoteArray($this->value, $connection)) . ")");
				break;
			case self::OPERATOR_IN:
				$sql = (empty($this->value) ? "" : "${field} IN (" . join(',', $this->quoteArray($this->value, $connection)) . ")");
				break;
			case self::OPERATOR_LIKE:
				$sql = "${field} LIKE {$this->quoteValue($this->value, $connection)}";
				break;
			case self::OPERATOR_GLOB:
				$sql = "${field} LIKE {$this->quoteValue(strtr($this->value, ['*'=>'%', '?'=>'_']), $connection)}";
				break;
			case self::OPERATOR_REV_GLOB:
				$sql = "{$this->quoteValue($this->value, $connection)} LIKE REPLACE(REPLACE(${field}, '*', '%'),'?','_')";
				break;
			case self::OPERATOR_REV_LIKE:
				$sql = "{$this->quoteValue($this->value, $connection)} LIKE ${field}";
				break;
			case self::OPERATOR_IN_STRING:
				$sql = "${field} LIKE '%{$this->quoteValue($this->value, $connection, false)}%'";
				break;
			case self::OPERATOR_STARTS:
				$sql = "${field} LIKE '%{$this->quoteValue($this->value, $connection, false)}''";
				break;
			case self::OPERATOR_ENDS:
				$sql = "${field} LIKE '{$this->quoteValue($this->value, $connection, false)}%'";
				break;
			case self::OPERATOR_REGEX:
				$sql = "${field} REGEXP '{$this->value}'";
				break;
			case self::OPERATOR_BETWEEN:
				$sql = "${field} BETWEEN {$this->quoteValue($this->value[0], $connection)} AND {$this->quoteValue($this->value[1], $connection)}";
				break;
		}
		$sql = $sql ? $sql : "";
		return $sql;
	}

	public function is($value){
		$this->operator = self::OPERATOR_IS;
		$this->value = $value;
		return $this;
	}

	public function not($value){
		$this->operator = self::OPERATOR_NOT_EQUAL;
		$this->value = $value;
		return $this;
	}

	public function isin($value){
		return is_array($value) ? $this->in($value) : $this->is($value);
	}

	public function in(array $value){
		$this->operator = self::OPERATOR_IN;
		$this->value = $value;
		return $this;
	}
	public function notIn(array $value){
		$this->operator = self::OPERATOR_NOT_IN;
		$this->value = $value;
		return $this;
	}

	public function between($min, $max){
		$this->operator = self::OPERATOR_BETWEEN;
		$this->value = [$min, $max];
		return $this;
	}

	public function isNull(){
		$this->operator = self::OPERATOR_IS_NULL;
		return $this;
	}

	public function isNotNull(){
		$this->operator = self::OPERATOR_IS_NOT_NULL;
		return $this;
	}

	public function revLike($value){
		$this->operator = self::OPERATOR_REV_LIKE;
		$this->value = $value;
		return $this;
	}

	public function like($value){
		$this->operator = self::OPERATOR_LIKE;
		$this->value = $value;
		return $this;
	}
	public function revGlob($value){
		$this->operator = self::OPERATOR_REV_GLOB;
		$this->value = $value;
		return $this;
	}

	public function glob($value){
		$this->operator = self::OPERATOR_GLOB;
		$this->value = $value;
		return $this;
	}

	public function instring($value){
		$this->operator = self::OPERATOR_IN_STRING;
		$this->value = $value;
		return $this;
	}

	public function startsWith($value){
		$this->operator = self::OPERATOR_STARTS;
		$this->value = $value;
		return $this;

	}

	public function endsWith($value){
		$this->operator = self::OPERATOR_ENDS;
		$this->value = $value;
		return $this;

	}

	public function matches($value){
		$this->operator = self::OPERATOR_REGEX;
		$this->value = $value;
		return $this;

	}

	public function gt($value){
		$this->operator = self::OPERATOR_GT;
		$this->value = $value;
		return $this;

	}
	public function gte($value){
		$this->operator = self::OPERATOR_GTE;
		$this->value = $value;
		return $this;

	}
	public function lt($value){
		$this->operator = self::OPERATOR_LT;
		$this->value = $value;
		return $this;

	}
	public function lte($value){
		$this->operator = self::OPERATOR_LTE;
		$this->value = $value;
		return $this;
	}
	public function raw(){
		$this->quote = false;
		return $this;
	}
}