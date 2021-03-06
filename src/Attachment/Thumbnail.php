<?php namespace Andesite\Attachment;

use Andesite\Attachment\Exception\SourceFileNotFound;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @property string $png
 * @property string $jpg
 * @property string $gif
 * @property string $url
 */
class Thumbnail{
	private $urlBase;
	private $path;
	private $sourcePath;

	private File $file;
	private $operation;
	private $jpegQuality;
	private $pathId;

	const CROP_MIDDLE = 0;
	const CROP_START = -1;
	const CROP_END = 1;
	private $secret;

	public function __construct(File $file, array $config){
		$this->file = $file;

		$this->sourcePath = $config['source-path'];
		$this->urlBase = $config['url'];
		$this->path = $config['path'];
		$this->secret = $config['secret'];
		$this->jpegQuality = $config['jpeg-quality'] ?? 80;

		if (strpos($file->getPath(), $this->sourcePath) !== 0) throw new SourceFileNotFound();
		$this->pathId = str_replace('/', '-', substr(trim($file->getPath(), '/'), strlen($this->sourcePath)));
	}

	public function purge(){
		$files = glob($this->path . '/' . $this->file->getFilename() . '.*.' . $this->pathId . '.*');
		foreach ($files as $file)
			unlink($file);
	}

	public function scale(int $width, int $height):self{
		$padding = 1;
		if ($width > 31 || $height > 31){
			$padding = 2;
		}
		if ($width > 1023 || $height > 1023){
			$padding = 3;
		}
		$width = str_pad(base_convert($width, 10, 32), $padding, '0', STR_PAD_LEFT);
		$height = str_pad(base_convert($height, 10, 32), $padding, '0', STR_PAD_LEFT);
		$this->operation = 's' . $width . $height;
		return $this;
	}

	public function crop(int $width, int $height, int $crop = 0):self{
		$padding = 1;
		if ($width > 31 || $height > 31){
			$padding = 2;
		}
		if ($width > 1023 || $height > 1023){
			$padding = 3;
		}
		$width = str_pad(base_convert($width, 10, 32), $padding, '0', STR_PAD_LEFT);
		$height = str_pad(base_convert($height, 10, 32), $padding, '0', STR_PAD_LEFT);

		$code = 'c';
		if ($crop == static::CROP_END)
			$code = 'c-';
		if ($crop == static::CROP_START)
			$code = 'c_';
		$this->operation = $code . $width . $height;
		return $this;
	}

	public function box(int $width, int $height):self{
		$padding = 1;
		if ($width > 31 || $height > 31){
			$padding = 2;
		}
		if ($width > 1023 || $height > 1023){
			$padding = 3;
		}
		$width = str_pad(base_convert($width, 10, 32), $padding, '0', STR_PAD_LEFT);
		$height = str_pad(base_convert($height, 10, 32), $padding, '0', STR_PAD_LEFT);

		$this->operation = 'b' . $width . $height;
		return $this;
	}

	public function width(int $width, int $maxHeight = 0, int $crop = 0):self{
		$padding = 1;
		if ($width > 31 || $maxHeight > 31){
			$padding = 2;
		}
		if ($width > 1023 || $maxHeight > 1023){
			$padding = 3;
		}
		$width = str_pad(base_convert($width, 10, 32), $padding, '0', STR_PAD_LEFT);
		$maxHeight = str_pad(base_convert($maxHeight, 10, 32), $padding, '0', STR_PAD_LEFT);

		$code = 'w';
		if ($crop == static::CROP_END)
			$code = 'w-';
		if ($crop == static::CROP_START)
			$code = 'w_';
		$this->operation = $code . $width . $maxHeight;
		return $this;
	}

	public function height(int $height, int $maxWidth = 0, int $crop = 0):self{
		$padding = 1;
		if ($height > 31 || $maxWidth > 31){
			$padding = 2;
		}
		if ($height > 1023 || $maxWidth > 1023){
			$padding = 3;
		}
		$height = str_pad(base_convert($height, 10, 32), $padding, '0', STR_PAD_LEFT);
		$maxWidth = str_pad(base_convert($maxWidth, 10, 32), $padding, '0', STR_PAD_LEFT);

		$code = 'h';
		if ($crop == static::CROP_END)
			$code = 'h-';
		if ($crop == static::CROP_START)
			$code = 'h_';
		$this->operation = $code . $height . $maxWidth;
		return $this;
	}

	public function exportGif():string { return $this->thumbnail('gif'); }

	public function exportPng():string { return $this->thumbnail('png'); }

	public function exportJpg(int $quality = null):string {
		if(!is_null($quality)) $this->jpegQuality = $quality;
		return $this->thumbnail('jpg');
	}

	public function export(int $quality = null):string {
		if(!is_null($quality)) $this->jpegQuality = $quality;
		$fileinfo = pathinfo($this->file);
		$ext = strtolower($fileinfo['extension']);
		if ($ext == 'jpeg')
			$ext = 'jpg';
		return $this->thumbnail($ext);
	}

	protected function thumbnail(string $ext): string{
		$op = $this->operation;
		if ($ext == 'jpg'){
			if ($this->jpegQuality < 0)
				$this->jpegQuality = 0;
			if ($this->jpegQuality > 100)
				$this->jpegQuality = 100;
			$op .= '.' . base_convert(floor($this->jpegQuality / 4), 10, 32);
		}

		$url = $this->file->getFilename() . '.' . $op . '.' . $this->pathId;
		$url = $this->urlBase . '/' . $url . '.' . base_convert(crc32($url . '.' . $ext . $this->secret), 10, 32) . '.' . $ext;

		return $url;
	}

	public function __get($name){
		switch ($name){
			case 'png':
				return $this->exportPng();
				break;
			case 'gif':
				return $this->exportGif();
				break;
			case 'jpg':
				return $this->exportJpg();
				break;
			case 'url':
				return $this->export();
				break;
		}
		return null;
	}

	public function __isset($name){
		return in_array($name, ['png', 'gif', 'jpg', 'url']);
	}
}