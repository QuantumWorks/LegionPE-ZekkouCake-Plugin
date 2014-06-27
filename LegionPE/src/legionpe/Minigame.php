<?php

namespace legionpe;

use pocketmine\event\Listener;

abstract class Minigame implements Listener{
	public abstract function getName();
	public function getDefaultChannel(){
		return Main::CHAT_CHANNEL_ROOT.".".strtolower($this->getName()).".global";
	}
	public abstract function getSpawn();
	public abstract function ownLevel($levelName);
}
