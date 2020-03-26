<?php namespace Andesite\Codex\Form;

class FormDecorator{
	protected $icon_header;
	protected $icon_form;
	protected $icon_tab;
	protected $icon_menu;
	protected $title;
	protected $title_memu;
	protected $url;
	protected $role;

	public function getIconHeader(): string{ return $this->icon_header; }
	public function getIconForm(): string{ return $this->icon_form; }
	public function getIconTab(): string{ return $this->icon_tab; }
	public function getIconMenu(): string{ return $this->icon_menu; }
	public function getTitle(): string{ return $this->title; }
	public function getTitleMenu(): string{ return $this->title_memu; }
	public function getUrl(): string{ return $this->url; }
	public function getRole(){ return $this->role; }

	public function __construct(string $icon, string $title, string $url){
		$this->url = $url;
		$this->setIcons($icon);
		$this->title = $title;
	}

	public function setIcons(string $header, string $form = null, string $tab = null, $menu = null){
		if (is_null($form)) $form = $header;
		if (is_null($tab)) $tab = $form;
		if (is_null($menu)) $menu = $tab;
		$this->icon_header = $header;
		$this->icon_tab = $tab;
		$this->icon_form = $form;
		$this->icon_menu = $form;
	}
	public function setTitle(string $title, string $menuTitle = null){
		$this->title_memu = $menuTitle ?: $title;
		$this->title = $title;
	}
	public function setRole(string $role){ $this->role = $role; }

}
