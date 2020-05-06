<?php namespace Andesite\Mission\Cli;

use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleTree{
	static function draw($array, SymfonyStyle $style, $root = '.'){
		$keys = array_keys($array);
		$last_key = end($keys);
		foreach ($array as $key => $value){
			$env[] = [$key === $last_key ? ' └─' : ' ├─', $key, is_array($value) ? null : ( is_null($value) ? '' : $value )];
			if (is_array($value)) static::tree($value, $env, [$key === $last_key]);

		}
		$style->writeln("\n".'<fg=cyan>['.$root.']</>');
		foreach ($env as $item){
			$style->write('' . $item[0] . ' ');
			if (is_null($item[2])){
				$style->writeln('<fg=cyan>' . $item[1] . '</>');
			}else{
				$style->write('<options=bold;fg=green>' . $item[1] . '</>: ');
				if ($item[2] === ''){
					$style->writeln('<fg=red;options=bold>-</>');
				}else{
					$style->writeln('<fg=black>' . $item[2] . '</>');
				}
			}
		}
	}
	protected static function tree($branch, &$env, $level){
		$keys = array_keys($branch);
		$last_key = end($keys);
		foreach ($branch as $key => $value){
			$leaf = '';
			for ($i = 0; $i < count($level); $i++){
				$leaf .= $level[$i] ? '   ' : ' │ ';
			}
			$leaf .= $last_key === $key ? ' └─' : ' ├─';
			$env[] = [$leaf, $key, is_array($value) ? null : ( is_null($value) ? '' : $value )];
			$l = $level;
			$l[] = ( $key === $last_key );
			if (is_array($value)) static::tree($value, $env, $l);
		}
	}
}