<?php namespace Andesite\Core\EnvConfig;

use Andesite\Core\Env\Env;
use Andesite\Core\ServiceManager\Service;
use Andesite\Core\ServiceManager\SharedService;

abstract class EnvConfig implements SharedService{

	use Service;

	public function __construct(Env $environment){
		foreach (get_object_vars($this) as $key => $envKey){
			if ($envKey){
				$this->$key = $environment->get($envKey);
			}
		}
	}
}