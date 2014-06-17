<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pemapmodder\legionpe\hub\Hub;
use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\CallbackEventExe;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Main extends MgMain implements Listener{
	/**
	 * @var Arena[]
	 */
	public $arenas = array();
	/**
	 * @var int[]
	 */
	public $sessions = array();
	public function __construct(){
		$this->hub = HubPlugin::get();
		// TODO initialize arenas with raw coords data
		// E.g.:
		/*
		for($id = 0; $id < $max; $id++){
			$centre = $this->getCentreById();
			$this->arenas[$id] = new Arena($id, $centre, 10, 4, 8, Block::get(80), Block::get(7), Block::get(20), Block::get(20));
		}
		*/
		$this->server = Server::getInstance();
		$pm = $this->server->getPluginManager();
		foreach(array(
				array("entity\\EntityMoveEvent", "onMove"),
				array("player\\PlayerInteractEvent", "onInteract"),) as $ev)
			$pm->registerEvent("pocketmine\\event\\".$ev[0], $this, EventPriority::HIGH, new CallbackEventExe(array($this, $ev[1])), HubPlugin::get());
	}
	public function onMove(EntityMoveEvent $evt){
		$p = $evt->getEntity();
		if(!($p instanceof Player)){
			return;
		}
		if($p->getLevel()->getName() === "world_spleef" and isset($this->sessions[$p->getID()])){
			if(($sid = $this->sessions[$p->getID()]) !== -1)
				if($this->arenas[$sid]->onMove($p) === false)
					$evt->setCancelled(true);
		}
	}
	public function onInteract(PlayerInteractEvent $evt){
		if($evt->getPlayer()->getLevel()->getName() !==
				Builder::spleef()->getName() or !isset($this->sessions[$evt->getPlayer()->getID()]))
			return;
		if(($sid = $this->sessions[$evt->getPlayer()->getID()]) !== -1){
			$this->arenas[$sid]->onInteract($evt);
		}
		else{
			for($i = 1; $i <= 4; $i++){
				if(Builder::signs($i)->isInside($evt->getBlock())){
					$this->join($i, $evt->getPlayer());
					break;
				}
			}
		}
	}
	public function join($SID, Player $player){
		if(($reason = $this->arenas[$SID]->isJoinable()) === true){
			$this->arenas[$SID]->join($player);
			$this->sessions[$player->getID()] = $SID;
		}
		else{
			$player->sendMessage("You can't join this arena! Reason: $reason");
		}
	}
	public function quit(Player $player){ // reset the player channel from the spleef
		$hub = Hub::get();
		if(strpos($hub->getWriteChannel($player), "spleef.s") !== false){
			$hub->setWriteChannel($player, $this->getDefaultChatChannel($player, $this->hub->getDb($player)->get("team")));
		}
		$this->sessions[$player->getID()] = -1;
	}
	public function getChance(Player $player){
		return $this->hub->config->get("spleef")["chances"][$this->hub->getRank($player)];
	}
	public function onJoinMg(Player $p){
		$this->sessions[$p->getID()] = -1;
	}
	public function onQuitMg(Player $p){
		if(!isset($this->sessions[$p->getID()])) return;
		if(($s = $this->sessions[$p->getID()]) !== -1){
			$this->arenas[$s]->quit($p, "logout");
		}
		unset($this->sessions[$p->getID()]);
	}
	public function getName(){
		return "Spleef";
	}
	public function getSessionId(){
		return HubPlugin::SPLEEF;
	}
	public function getSpawn(Player $player, $TID){
		return Builder::spleefSpawn();
	}
	public function getDefaultChatChannel(Player $player, $TID){
		return "legionpe.chat.spleef.public";
	}
	public function isJoinable(Player $player, $t){
		return true;
	}
	public function getStats(Player $player, array $args = []){
		if(!isset($args[0]) or strtolower($args[0]) !== "top"){
			$db = $this->hub->getDb($player)->get("spleef");
			return "You have ".$db["wins"]." wins and ".$db["unwons"]." losses.";
		}
		return yaml_emit($this->hub->config->get("spleef")["top-wins"]);
	}
	public function getPermission(){
		return "legionpe.mg.spleef";
	}
	public function getArena(Player $player){
		return $this->sessions[$player->getID()];
	}
	public static $instance = false;
	/**
	 * @return self
	 */
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
}
