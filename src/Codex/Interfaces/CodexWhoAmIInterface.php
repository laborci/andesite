<?php namespace Andesite\Codex\Interfaces;

use Andesite\Zuul\Interfaces\WhoAmIInterface;

interface CodexWhoAmIInterface extends WhoAmIInterface{

	public function getName():string;
	public function getAvatar():string;

}