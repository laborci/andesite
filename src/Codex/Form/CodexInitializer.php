<?php namespace Andesite\Codex\Form;


use Application\AdminCodex\Menu;
use Andesite\Codex\Form\AdminRegistry;
use Andesite\Codex\Interfaces\CodexWhoAmIInterface;
use Andesite\Util\CodeFinder\CodeFinder;

class CodexInitializer{

	public $title = 'Codex Codex';
	public $icon = 'fak fa-infinite';
	public $loginPlaceholder = 'login';
	private $whoAmI;

	public function __construct(CodexWhoAmIInterface $whoAmI){
		$this->whoAmI = $whoAmI;
	}

	public function getMenu(){
		$menu = new CodexMenu();
		$this->menu($menu);
		return $menu->extract($this->whoAmI);
	}

	protected function autoMap($namespace, AdminRegistry $registry){
		$cw = new CodeFinder();
		$classes = $cw->Psr4ClassSeeker($namespace);
		foreach ($classes as $class){
			$registry->registerForm($class);
		}
	}
}