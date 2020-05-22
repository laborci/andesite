<?php namespace Andesite\Codex;

use Andesite\Codex\Form\AdminRegistry;
use Andesite\Codex\Form\CodexMenu;
use Andesite\Codex\Interfaces\CodexWhoAmIInterface;
use Andesite\Codex\Page\Index;
use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Ghost\GhostManager;
use Andesite\Mission\Web\Routing\Router;
use Andesite\Mission\Web\WebMission;
use Andesite\TwigResponder\TwigResponder;
use Andesite\Util\CodeFinder\CodeFinder;
use Andesite\Zuul\Auth\Auth;
use Andesite\Zuul\Auth\AuthService;
use Andesite\Zuul\Interfaces\AuthServiceInterface;
use Andesite\Zuul\Web\Action\Login;
use Andesite\Zuul\Web\Action\Logout;
use Andesite\Zuul\Web\Middleware\AuthCheck;
use Andesite\Zuul\Web\Middleware\RoleCheck;
use Application\AdminCodex\Form\ArticlePodcastCodex;
use Application\Service\Auth\CodexWhoAmI;

abstract class CodexMission extends WebMission{

	protected $frontendPrefix;
	protected $role;
	protected $icon;
	protected $loginPlaceholder;
	protected $title;
	/** @var \Andesite\Codex\Form\AdminRegistry */
	protected $adminRegistry;
	/** @var \Andesite\Codex\Interfaces\CodexWhoAmIInterface */
	protected $whoAmI;
	protected $formAutomap;
	protected $whoAmIClass;

	abstract protected function menu(CodexMenu $menu);

	protected function load(Auth $auth, TwigResponder $twigResponder){
		$this->whoAmI = $this->getWhoAmI();
		$twigResponder->addTwigPath('codex', __DIR__ . '/@resource/');
		$this->adminRegistry = new AdminRegistry();
		$this->importForms($this->adminRegistry);
	}

	public function getMenu(){
		$menu = new CodexMenu();
		$this->menu($menu);
		return $menu->extract($this->whoAmI);
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
				"role"              => "Admin",
				"form-automap"      => "Application\AdminCodex\Form",
				"frontend-prefix"   => "~admin/",
				"who-am-i"          => CodexWhoAmI::class,
				"auth-service"      => AuthService::class,
			], $config);

		ServiceContainer::shared(AuthServiceInterface::class, $config["auth-service"]);
		ServiceContainer::shared(CodexWhoAmIInterface::class, $config["who-am-i"]);

		$this->title = $config['title'];
		$this->icon = $config['icon'];
		$this->loginPlaceholder = $config['login-placeholder'];
		$this->role = $config['role'];
		$this->formAutomap = $config['form-automap'];
		$this->frontendPrefix = $config['frontend-prefix'];
	}

	public function route(Router $router){

		$router->post("/login", Login::class, ['role' => $this->role])();
		$router->pipe(AuthCheck::class, AuthCheck::config(\Andesite\Codex\Page\Login::class));

		if ($this->role){
			$router->pipe(RoleCheck::class, RoleCheck::config(\Andesite\Codex\Page\Login::class, $this->role, true));
		}
		$router->post("/logout", Logout::class)();

		// PAGES
		GhostManager::Module()->routeThumbnail($router);
		$router->get("/", Index::class)();

		// API
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

		$router->get('/', Page\Index::class)();
	}

	public function getFrontendPrefix(){ return $this->frontendPrefix; }

	public function getRole(){ return $this->role; }

	public function getIcon(){ return $this->icon; }

	public function getLoginPlaceholder(){ return $this->loginPlaceholder; }

	public function getTitle(){ return $this->title; }

	/** @return \Andesite\Codex\Form\AdminRegistry */
	public function getAdminRegistry(): \Andesite\Codex\Form\AdminRegistry{ return $this->adminRegistry; }

	/** @return \Andesite\Codex\Interfaces\CodexWhoAmIInterface */
	public function getWhoAmI(): \Andesite\Codex\Interfaces\CodexWhoAmIInterface{ return ServiceContainer::get(CodexWhoAmIInterface::class); }

	public function getFormAutomap(){ return $this->formAutomap; }
	
}
