<?php

namespace legionpe;

use legionpe\minigames\ctf\CTF;
use legionpe\minigames\kitpvp\KitPvp;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{
	const CHAT_CHANNEL_ROOT = "legionpe.chat";
	private $sessioner;
	private $minigames = [];
	public function onEnable(){
		$this->registerMinigame(new CTF($this));
		$this->registerMinigame(new KitPvp($this));
	}
	public function registerMinigame(Minigame $mg){
		$this->minigames[$mg->getName()] = $mg;
	}
}
