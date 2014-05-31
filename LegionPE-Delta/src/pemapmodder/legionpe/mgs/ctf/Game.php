<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\HubPlugin;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;

class Game{
	/**
	 * @var Player[][]
	 */
	public $players = array(
		0 => array(),
		1 => array(),
		2 => array(),
		3 => array()
	);
	public function __construct(Level $level){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
	}
	public function join(Player $p){
		$tid = $this->hub->getDb($p)->get("team");
		$p->teleport(RawLocs::pSpawn($tid));
		$this->players[$tid] = $p;
	}
	public function quit(Player $p){
	}
	public function finalize($reason = "server stop"){
		$this->broadcast("Match ended. Reason: $reason.");
	}
}
