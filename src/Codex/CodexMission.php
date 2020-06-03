<?php namespace Andesite\Codex;

use Andesite\Codex\Action\Login;
use Andesite\Codex\Action\Logout;
use Andesite\Codex\Form\AdminRegistry;
use Andesite\Codex\Form\CodexMenu;
use Andesite\Codex\Interfaces\AuthInterface;
use Andesite\Codex\Middleware\AuthCheck;
use Andesite\Codex\Middleware\RoleCheck;
use Andesite\Codex\Page\Index;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Ghost\GhostManager;
use Andesite\Mission\Web\Action\NotAuthorized;
use Andesite\Mission\Web\Routing\Router;
use Andesite\Mission\Web\WebMission;
use Andesite\TwigResponder\TwigResponder;
use Andesite\Util\CodeFinder\CodeFinder;

abstract class CodexMission extends WebMission{

	protected $frontendPrefix;
	protected $icon;
	protected $loginPlaceholder;
	protected $title;
	protected $userName;
	protected $userAvatar;
	/** @var \Andesite\Codex\Form\AdminRegistry */
	protected $adminRegistry;
	protected $formAutomap;

	abstract protected function menu(CodexMenu $menu);

	protected function load(TwigResponder $twigResponder){
		$twigResponder->addTwigPath('codex', __DIR__ . '/@resource/');
		$this->adminRegistry = new AdminRegistry();
		$this->importForms($this->adminRegistry);
	}

	public function getMenu(){
		$menu = new CodexMenu();
		$this->menu($menu);
		return $menu->extract(ServiceContainer::get(AuthInterface::class));
	}

	protected function automap($namespace, AdminRegistry $registry){
		$cw = new CodeFinder();
		$classes = $cw->Psr4ClassSeeker($namespace);
		foreach ($classes as $class){
			$reflection = new \ReflectionClass($class);
			if (!$reflection->isAbstract()) $registry->registerForm($class);
		}
	}

	public function importForms(AdminRegistry $registry){ $this->automap($this->formAutomap, $registry); }

	public function setup($config){
		if(!is_array($config)) $config = [];
		$config = array_merge(
			[
				"title"             => "Admin",
				"icon"              => "fas fa-user-secret",
				"login-placeholder" => "email",
				"form-automap"      => "Application\AdminCodex\Form",
				"frontend-prefix"   => "~admin/"
			], $config);

		$this->title = $config['title'];
		$this->icon = $config['icon'];
		$this->loginPlaceholder = $config['login-placeholder'];
		$this->formAutomap = $config['form-automap'];
		$this->frontendPrefix = $config['frontend-prefix'];
		$this->userName = 'admin';
		$this->userAvatar = '';
	}

	public function route(Router $router){

		$router->post("/login", Login::class)();

		// API
		$router->pipe(AuthCheck::class, AuthCheck::config(NotAuthorized::class));

		$router->get('/menu', Action\CodexMenu::class)();
		$router->get('/{form}/codexinfo', Action\CodexInfo::class)();
		$router->post('/{form}/get-list/{page}', Action\CodexGetList::class)();
		$router->get('/{form}/get-form-item/{id}', Action\CodexGetFormItem::class)();
		$router->get('/{form}/get-form', Action\CodexGetForm::class)();
		$router->post('/{form}/save-item', Action\CodexSaveFormItem::class)();
		$router->get('/{form}/delete-item/{id}', Action\CodexDeleteFormItem::class)();
		$router->post('/{form}/attachment/upload/{id}', Action\CodexAttachmentUpload::class)();
		$router->get('/{form}/attachment/get/{id}', Action\CodexAttachmentGet::class)();
		$router->post('/{form}/attachment/move/{id}', Action\CodexAttachmentMove::class)();
		$router->post('/{form}/attachment/copy/{id}', Action\CodexAttachmentCopy::class)();
		$router->post('/{form}/attachment/delete/{id}', Action\CodexAttachmentDelete::class)();

		// PAGES
		$router->clearPipeline();
		$router->pipe(AuthCheck::class, AuthCheck::config(\Andesite\Codex\Page\Login::class));
		$router->post("/logout", Logout::class)();
		GhostManager::Module()->routeThumbnail($router);
		$router->get('/', Page\Index::class)();
	}

	public function getFrontendPrefix(){ return $this->frontendPrefix; }
	public function getIcon(){ return $this->icon; }
	public function getLoginPlaceholder(){ return $this->loginPlaceholder; }
	public function getTitle(){ return $this->title; }
	/** @return \Andesite\Codex\Form\AdminRegistry */
	public function getAdminRegistry(): \Andesite\Codex\Form\AdminRegistry{ return $this->adminRegistry; }
	public function getFormAutomap(){ return $this->formAutomap; }
	public function getUserName(){return $this->userName;}
	public function getUserAvatar(){return $this->userAvatar;}
	
}
