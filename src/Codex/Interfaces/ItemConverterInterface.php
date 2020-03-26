<?php namespace Andesite\Codex\Interfaces;

interface ItemConverterInterface{
	public function convertItem($item):array;
}