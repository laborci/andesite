<?php namespace Andesite\CliCommand;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\Mission\Cli\CliCommand;
use Andesite\Mission\Cli\CliModule;
use Andesite\Util\CodeFinder\CodeFinder;
use Andesite\Util\DotArray\DotArray;
use CaseHelper\CaseHelperFactory;
use Minime\Annotations\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExtractApi extends CliModule{

	protected function createCommand($config): Command{

		return new class( $config, 'generate:api-js', 'api', 'Extract web api-s to js') extends CliCommand{

			protected function runCommand(SymfonyStyle $style, InputInterface $input, OutputInterface $output, $config){
				$apis = $config;
				foreach ($apis as $api) if (!empty($api['extract'])) $this->extract($api);
				$style->success('done');
			}

			protected function extract($api){
				/** @var Reader $reader */
				$reader = ServiceContainer::get(Reader::class);

				$namespace = $api['namespace'];
				$path = $api['path'];

				$cw = CodeFinder::Service();
				$classes = $cw->Psr4ClassSeeker($namespace);

				$endpoints = [];

				foreach ($classes as $class){

					$url = explode('\\', trim($path, '/') . substr($class, strlen($namespace)));
					$url = array_map(function ($url){ return CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE)->toKebabCase($url); }, $url);

					$reflection = new \ReflectionClass($class);
					/** @var \ReflectionMethod $classMethod */
					$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
					$prefix = $reader->getClassAnnotations($class)->get('endpoint-prefix', false);
					echo $class . ' ';
					echo $prefix . "\n";

					foreach ($methods as $method){
						$on = $reader->getAnnotations($method)->get('on');
						$accepts = $reader->getAnnotations($method)->get('accepts');
						$name = $reader->getAnnotations($method)->get('endpoint');
						if (( $accepts || $on ) && $name){
							$endpoint = [
								'name'     => ( $prefix ? $prefix . '.' : '' ) . $name,
								'url'      => join('/', $url) . ( $on ? '' : '/' . CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_PASCAL_CASE)->toKebabCase($method->getName()) ),
								'params'   => array_column(array_map(function (\ReflectionParameter $parameter){ return [$parameter->getName(), $parameter->isOptional()]; }, $method->getParameters()), 1, 0),
								'required' => $method->getNumberOfRequiredParameters(),
							];
							$endpoints[$endpoint['name']] = $endpoint;
						}
					}
				}

				$endpointMethods = [];
				foreach ($endpoints as $key => $endpoint){
					$endpointMethod = '';

					$endpointMethod = "\t/**\n";
					foreach ($endpoint['params'] as $param => $optional) $endpointMethod .= "\t * @param " . ( $optional ? "[" . $param . "]" : $param ) . "\n";
					$endpointMethod .= "\t * @return {string}\n";
					$endpointMethod .= "\t */\n";

					$endpointMethod .= "\t" . " function(" . join(", ", array_keys($endpoint['params'])) . "){\n";
					if ($endpoint['required']) $endpointMethod .= "\t\tif(arguments.length < " . $endpoint['required'] . ") throw new Error();\n";
					$endpointMethod .= "\t\treturn \"/" . $endpoint['url'] . ( !empty($endpoint['params']) ? "/\" + [...arguments].join('/')" : "\"" ) . ";\n";
					$endpointMethod .= "\t}";
					$endpointMethods[$key] = $endpointMethod;
				}

				$keys = array_keys($endpointMethods);
				$m = [];
				foreach ($keys as $key){
					DotArray::set($m, $key, '{{' . $key . '}}');
				}

				foreach ($api['extract'] as $extract){

					$file = $extract['file'];
					$as = $extract['as'];

					$output = "let " . $as . " = " . json_encode($m, JSON_PRETTY_PRINT) . ";\nexport default " . $as . ";";
					foreach ($endpointMethods as $name => $endpointMethod){
						$output = str_replace('"{{' . $name . '}}"', "\n" . $endpointMethod . "\n\t\t", $output);
					}
					//file_put_contents( $file, "let " . $as . " = {\n" . join(",\n", $endpointMethods) . "\n};\nexport default " . $as . ";");
					file_put_contents($file, $output);
				}
			}
		};
	}

}
