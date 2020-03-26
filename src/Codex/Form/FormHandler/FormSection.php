<?php namespace Andesite\Codex\Form\FormHandler;

use Andesite\Codex\Form\AdminDescriptor;
use JsonSerializable;
use Andesite\Codex\Form\Field;

class FormSection implements JsonSerializable{
	/** @var \Andesite\Codex\Form\FormHandler\FormInput[] */
	protected $inputs = [];
	protected $label;
	protected $adminDescriptor;

	public function __construct($label, AdminDescriptor $adminDescriptor){
		$this->label = $label;
		$this->adminDescriptor = $adminDescriptor;
	}

	public function input($type, Field $field, $label = null){
		if (is_null($label)){
			$label = $field->label;
		}
		$input = new FormInput($type, $label, $field->name);
		$this->inputs[] = $input;
		return $input;
	}

	public function jsonSerialize(){
		return [
			'label'  => $this->label,
			'inputs' => $this->inputs,
		];
	}

	/** @return \Andesite\Codex\Form\FormHandler\FormInput[] */
	public function getInputs(): array{ return $this->inputs; }

}
