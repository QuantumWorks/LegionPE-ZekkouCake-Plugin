<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\HubPlugin;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;

class Game{
	public function __construct(Level $level){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
	}
	public function join(Player $p){
	}
	public function quit(Player $p){
	}
	public function finalize($reason){
	}
}
