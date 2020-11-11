<?php namespace Andesite\Magic;

use Andesite\Core\Env\Env;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class GhostAttachmentAdapter implements AttachmentAdapterInterface{

	/** @var \Andesite\Ghost\Ghost */
	private $ghost;
	/** @var \Andesite\Ghost\Ghost */
	private $item;
	private $collections;
	private Request $request;
	private Response $response;
	private ParameterBag $jsonParamBag;

	public function __construct($ghost, $collections){
		$this->ghost = $ghost;
		$this->collections = $collections;
	}

	public function handle(string $action, Request $request, Response $response){
		$this->request = $request;
		$this->response = $response;
		$this->item = $this->ghost::pick($this->request->request->get('id'));

		switch ($action){
			case 'get':
				return $this->get();
			case 'upload':
				return $this->upload(
					$this->request->request->get('collection'),
					$this->request->files->get('file')
				);
			case 'delete':
				return $this->delete(
					$this->request->request->get('collection'),
					$this->request->request->get('filename')
				);
			case 'rename':
				return $this->rename(
					$this->request->request->get('collection'),
					$this->request->request->get('filename'),
					$this->request->request->get('newname'));
			case 'copy':
				return $this->copy(
					$this->request->request->get('filename'),
					$this->request->request->get('source'),
					$this->request->request->get('target')
				);
			case 'move':
				return $this->move(
					$this->request->request->get('filename'),
					$this->request->request->get('source'),
					$this->request->request->get('target')
				);
			case 'reorder':
				return $this->reorder(
					$this->request->request->get('filename'),
					$this->request->request->get('collection'),
					$this->request->request->get('ordinal')
				);
			case 'crop':
				return $this->crop(
					$this->request->request->get('filename'),
					$this->request->request->get('collection'),
					json_decode($this->request->request->get('data'), true)
				);
		}
	}

	protected function get(){
		$collections = $this->request->request->get('collections');
		$attachments = [];
		foreach ($collections as $collection){
			$attachments[$collection] = [];
			foreach ($this->item->getAttachmentCollection($collection) as $file){
				$data = $file->jsonSerialize();
				if ($file->isImage) $data['thumbnail'] = $file->thumbnail->crop(196, 196)->jpg;
				$attachments[$collection][] = $data;
			}
		}
		return $attachments;
	}

	protected function upload($collection, UploadedFile $file){
		try{
			$this->item->getAttachmentCollection($collection)->addFile($file);
		}catch (\Throwable $exception){
			$this->response->setStatusCode(400);
			return[
				'exception'=>get_class($exception),
				'message'=>$exception->getMessage()
			];
		}
		return [];
	}

	protected function delete($collection, $filename){
		$this->item->getAttachmentCollection($collection)->get($filename)->delete();
		return [];
	}

	protected function rename($collection, $filename, $newname){
		$this->item->getAttachmentCollection($collection)->get($filename)->rename($newname);
		return [];
	}

	protected function copy($filename, $source, $target){
		$this->item->getAttachmentCollection($target)->addFile($this->item->getAttachmentCollection($source)->get($filename)->file);
		return [];
	}

	protected function move($filename, $source, $target){
		$this->item->getAttachmentCollection($target)->addFile($this->item->getAttachmentCollection($source)->get($filename)->file);
		$this->item->getAttachmentCollection($source)->get($filename)->delete();
		return [];
	}

	protected function reorder($filename, $collection, $ordinal){
		$this->item->getAttachmentCollection($collection)->get($filename)->setSequence($ordinal);
		return [];
	}


	protected function crop($filename, $collection, $data){
		$file = $this->item->getAttachmentCollection($collection)->get($filename)->file->getRealPath();
		$imgInfo = getimagesize($file);
		$oType = $imgInfo['2'];
		switch ($oType){
			case 1:
				$img = imagecreatefromgif($file);
				break;
			case 2:
				$img = imagecreatefromjpeg($file);
				break;
			case 3:
				$img = imagecreatefrompng($file);
				break;
			default:
				throw new Exception('unsupported file');
		}

		$width = $data['width'];
		$height = $data['height'];
		$x = $data['x'];
		$y = $data['y'];

		$newImg = imageCreateTrueColor($width, $height);
		imagefill($newImg, 0, 0, imagecolorallocatealpha($newImg, 0, 0, 0, 127));

		imagecopyresampled($newImg, $img, 0, 0, $x, $y, $width, $height, $width, $height);
		imagedestroy($img);

		$output = Env::Service()->get('path.tmp') . '/' . uniqid() . '.png';
		ImagePng($newImg, $output);
		$this->item->getAttachmentCollection($collection)->addFile(new File($output));
		unlink($output);
		return [];
	}

}