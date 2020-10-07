<?php namespace Andesite\TwigResponder\Responder;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Mission\Web\Responder\PageResponder;
use Andesite\TwigResponder\TwigResponder;
use Minime\Annotations\Reader;
use Minime\Annotations\Interfaces\AnnotationsBagInterface;

abstract class TwigPageResponder extends PageResponder{

	/** @var AnnotationsBagInterface */
	protected $annotations;
	protected $template;
	protected $selfViewModel;

	public function __construct(){
		/** @var Reader $annotationReader */
		$annotationReader = ServiceContainer::get(Reader::class);
		$this->annotations = $annotationReader->getClassAnnotations(get_called_class());
		$this->template = $this->annotations->get('template');
		$this->selfViewModel = $this->annotations->has('selfViewModel');
	}
	protected function respond(): string{ return TwigResponder::Module()->render($this->template, $this->createViewModel()); }
	protected function createViewModel(){ return $this->selfViewModel ?  call_user_func('get_object_vars', $this) : $this->getDataBag()->all(); }
	protected function set($name, $value = null){
		if(is_array($name))foreach($name as $key=>$value){
			$this->getDataBag()->set($key, $value);
		}else $this->getDataBag()->set($name, $value);
	}
	protected function get($key){return $this->getDataBag()->get($key);}

}