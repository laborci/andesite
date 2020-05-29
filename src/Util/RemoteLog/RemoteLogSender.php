<?php namespace Andesite\Util\RemoteLog;

use Andesite\Core\ServiceManager\ServiceContainer;
use Andesite\DBAccess\Connection\SqlLogInterface;
use Andesite\Util\Dumper\DumpInterface;
use Andesite\Util\ErrorHandler\ExceptionHandlerInterface;
use Andesite\Util\ErrorHandler\FatalErrorHandlerInterface;

use Symfony\Component\HttpFoundation\Request;

class RemoteLogSender extends AbstractRemoteLogSender{

	protected function send($address, $message){
		$post_string = json_encode($message);
		$parts = parse_url($address.'/');
		try{
			$fp = @fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
			if ($fp){
				$out = "POST " . $parts['path'] . " HTTP/1.1\r\n";
				$out .= "Host: " . $parts['host'] . "\r\n";
				$out .= "Content-Type: application/json\r\n";
				$out .= "Content-Length: " . strlen($post_string) . "\r\n";
				$out .= "Connection: Close\r\n\r\n";
				if (isset($post_string))
					$out .= $post_string;
				fwrite($fp, $out);
				fclose($fp);
			}
		}catch (\Exception $ex){
		}
	}


}

