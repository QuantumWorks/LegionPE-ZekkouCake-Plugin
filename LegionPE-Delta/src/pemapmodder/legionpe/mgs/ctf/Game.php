<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\HubPlugin;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;

class Game implements Listener{
	const FL_CONCEAL = 0;
	const FL_RETURN = 1;
	const FL_DROP = 2;
	const RL_WAIT = 0; // prestart or paused
	const RL_PLAY = 1;
	const RL_DONE = 2;
	/** @var Player[][] */
	private $players = array(
		0 => array(),
		1 => array(),
		2 => array(),
		3 => array()
	);
	/** @var Player[]|int[] */
	private $flagSession = array(
		0 => self::FL_CONCEAL,
		1 => self::FL_CONCEAL,
		2 => self::FL_CONCEAL,
		3 => self::FL_CONCEAL
	);
	/**
	 * @var int
	 */
	private $runSession = self::RL_WAIT;
	public function __construct(Level $level){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$this->server->getPluginManager()->registerEvents($this, $this->hub);
	}
	public function join(Player $p){
		$tid = $this->hub->getDb($p)->get("team");
		$p->teleport(RawLocs::pSpawn($tid));
		$this->players[$tid][] = $p->getID();
	}
	public function quit(Player $p){
		if(is_int($t = $this->getTeam($p, $key))){
			unset($this->players[$t][$key]);
		}
	}
	public function onInteract(PlayerInteractEvent $event){
		if($this->hub->getSession($event->getPlayer()) !== HubPlugin::CTF){
			return;
		}
		$this->getTeam($event->getPlayer());
		$id = Rawlocs::identifyBlock($event->getBlock());
		if($id === false){
			return;
		}
		$event->setCancelled(true);
		switch($id){
			case Rawlocs::FLAG_0:
			case RawLocs::FLAG_1:
			case RawLocs::FLAG_2:
			CASE Rawlocs::FLAG_3:
				return;
		}
	}
	public function getTeam(Player $player, &$key = false){
		$clone = $key;
		for($i = 0; $i < 4; $i++){
			$key = array_search($player->getID(), $this->players[$i]);
			if($key !== false){
				return $i;
			}
		}
		$key = $clone;
		return false;
	}
	public function finalize($reason = "server stop"){
		$this->broadcast("Match ended. Reason: $reason.");
	}
	public function broadcast($message, $teams = [0, 1, 2, 3]){
		foreach($teams as $team){
			/** @var Player $player */
			foreach($this->players[$team] as $player){
				$player->sendMessage("[CTF] $message");
			}
		}
	}
}
