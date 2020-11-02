<?php namespace Andesite\Magic;

interface FormAdapterInterface{

	public function get($id);
	public function delete($id);
	public function save($id, $data);

}