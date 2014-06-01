<?php

namespace pemapmodder\utils;

use pocketmine\plugin\Plugin;
use pocketmine\scheduler\PluginTask;

class CallbackPluginTask extends PluginTask{
	/**
	 * @param callable $callback
	 * @param Plugin $owner
	 * @param mixed|array $data
	 * @param bool $asArray
	 */
	public function __construct(callable $callback, Plugin $owner, $data = array(), $asArray = false){
		parent::__construct($owner);
		$this->cb = $callback;
		$this->data = $data;
		$this->useArray = $asArray;
	}
	public function onRun($t){
		if($this->useArray === true)
			call_user_func_array($this->cb, $this->data);
		elseif($this->useArray === 2){
			call_user_func($this->cb, $t);
		}
		else{
			call_user_func($this->cb, $this->data);
		}
	}
}
