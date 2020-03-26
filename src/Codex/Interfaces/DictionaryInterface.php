<?php namespace Andesite\Codex\Interfaces;

interface DictionaryInterface{
	public function __invoke($key):string;
	public function getDictionary():array;
}