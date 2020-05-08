<?php namespace Andesite\Codex\Action;

use Andesite\Core\Boot\Andesite;
use Andesite\Mission\Web\Responder\JsonResponder;
use Andesite\Zuul\Interfaces\AuthServiceInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class Responder extends JsonResponder{

	/** @var \Andesite\Codex\Form\AdminDescriptor */
	protected $adminDescriptor;
	/** @var \Andesite\Codex\Form\FormDecorator */
	protected $formDecorator;
	/** @var AuthServiceInterface */
	protected $authService;
	/** @var \Andesite\Codex\Form\AdminRegistry */
	private $adminRegistry;

	public function __construct(AuthServiceInterface $authService){
		/** @var \Andesite\Codex\CodexMission $mission */
		$mission = Andesite::mission();
		$this->authService = $authService;
		$this->adminRegistry = $mission->getAdminRegistry();
	}

	protected function respond(){
		$this->adminDescriptor = $this->adminRegistry->get($this->getPathBag()->get('form'));
		$this->formDecorator = $this->adminDescriptor->getDecorator();
		if (!$this->authService->checkRole($this->formDecorator->getRole())){
			$this->getResponse()->setStatusCode(Response::HTTP_FORBIDDEN);
			return false;
		}else return $this->codexRespond();
	}
	
	abstract protected function codexRespond(): ?array;
}