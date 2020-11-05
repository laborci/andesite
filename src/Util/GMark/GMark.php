<?php namespace Andesite\Util\GMark;

use Minime\Annotations\Reader;


abstract class GMark{

	private $commands = [];
	private $annotations;

	private $defaultBlockMethod;

	public function __construct(Reader $annotationReader){
		$this->annotations = $annotationReader->getClassAnnotations(get_called_class());

		$reflector = new \ReflectionClass($this);
		$methods = $reflector->getMethods();

		foreach ($methods as $method){
			$annotations = ( $annotationReader->getMethodAnnotations(get_called_class(), $method->getName()) );

			if ($annotations->has('GMark')){
				$command = $annotations->get('GMark');
				$params = $method->getParameters();
				$attributes = [];
				array_shift($params);
				array_shift($params);
				foreach ($params as $param){

					$attribute = [
						'name'     => $param->name,
						'required' => !$param->isOptional(),
						'default'  => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
					];
					if ($annotations->has($param->name . '-options')) $attribute['options'] = $annotations->getAsArray($param->name . '-options');

					$attributes[] = $attribute;
				}

				$this->commands[$command] = [
					'method'      => $method->name,
					'as'          => $command,
					'attributes'  => $attributes,
					'body'        => !$annotations->has('nobody'),
					'description' => $annotations->has('description') ? $annotations->get('description') : $command,
					'icon'        => $annotations->has('icon') ? $annotations->get('icon') : '',
				];

			}
		}
	}

	public function parse($string){
		$string = preg_replace("/[\r\n]{2,}/", "\n\n", trim($string));
		$parts = explode("\n\n", $string);
		$blocks = [];

		foreach ($parts as $part){
			$block = $this->parseBlock(trim($part));
			if (is_null($block['command']) && count($blocks)){
				$blocks[count($blocks) - 1]['body'] .= "\n\n" . $block['body'];
			}else{
				$blocks[] = $block;
			}
		}
		$output = [];

		foreach ($blocks as $block){
			if (!is_null($block['command'])){
				$method = $block['command']['method'];
				$output[] = $this->$method($block['command']['as'], $block['body'], ...$block['params']);
			}else{
				$output[] = $this->defaultParser($block['body']);
			}
		}
		return $this->joinBlocks($output);
	}

	protected function defaultParser($block){
		return $block;
	}

	protected function joinBlocks($blocks){ return join("\n", $blocks); }

	private function parseBlock($block){

		$command = preg_split('/\s+/', $block, 2)[0];

		if (array_key_exists($command, $this->commands)){
			$command = $this->commands[$command];

			[$commandLine, $body] = array_pad(explode("\n", $block, 2), 2, null);
			$attr = trim(array_pad(preg_split('/\s+/', $commandLine, 2), 2, null)[1]);
			try{
				$attrs = $this->parseAttributes($attr);
			}catch (\Throwable $exception){
				return [
					"command" => null,
					"params"  => [],
					"body"    => 'ATTRIBUTES COULD NOT BE PARSED in line: ' . $commandLine,
				];
			}
			$params = [];
			foreach ($command['attributes'] as $attribute){
				if ($attribute['required'] && !array_key_exists($attribute['name'], $attrs)){
					return [
						"command" => null,
						"params"  => [],
						"body"    => 'ATTRIBUTE ' . $attribute['name'] . ' MISSING in line: ' . $commandLine,
					];
				}
				$params[] = array_key_exists($attribute['name'], $attrs) ? $attrs[$attribute['name']] : $attribute['default'];
			}

			return [
				'command' => $command,
				'body'    => $body ? $body : '',
				'params'  => $params,
			];
		}else{
			return [
				'command' => null,
				'body'    => $block,
				'params'  => [],
			];
		}
	}

	private function parseAttributes($attributes){
		$x = (array)new \SimpleXMLElement("<element $attributes />");
		return current($x);
	}

	public function getCommands(){ return $this->commands; }
}
