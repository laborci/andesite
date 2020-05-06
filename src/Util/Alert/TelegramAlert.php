<?php namespace Andesite\Util\Alert;

class TelegramAlert implements AlertInterface{

	private $bot;
	private $channels;
	public function __construct($bot){
		$this->bot = $bot['id'];
		$this->channels = $bot['channel'];
		if(!is_array($this->channels)) $this->channels = [$this->channels];
	}

	public function alert($message){
		foreach ($this->channels as $channel){
			$data = [
				'chat_id' => $channel,
				'text'    => $message
			];
			file_get_contents('https://api.telegram.org/bot' . $this->bot . '/sendMessage?' . http_build_query($data));
		}
	}
}
