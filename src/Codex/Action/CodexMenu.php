<?php namespace Andesite\Codex\Action;

use Andesite\Core\Boot\Andesite;
use Andesite\Mission\Web\Responder\JsonResponder;

class CodexMenu extends JsonResponder{
	
	protected function respond(): ?array{
		/** @var \Andesite\Codex\CodexMission $mission */
		$mission = Andesite::mission();
		return $mission->getMenu();
	}

}

