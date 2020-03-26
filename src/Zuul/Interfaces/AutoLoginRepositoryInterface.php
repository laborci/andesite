<?php namespace Andesite\Zuul\Interfaces;

Interface  AutoLoginRepositoryInterface{
	public function create($userId):string;
	public function findByToken($token):?int;
	public function delete($token);
	public function update($token);
}