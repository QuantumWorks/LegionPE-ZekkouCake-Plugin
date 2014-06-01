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
use pocketmine\block\Block;
use pocketmine\event\Event;
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
	/**
	 * @var \pocketmine\permission\PermissionAttachment[]
	 */
	protected $atchmts = array();
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
		if($evt->getEntity() instanceof Player and $evt->getEntity()->getLevel()->getName() === "world_spleef" and isset($this->sessions[$evt->getEntity()->CID])){
			if(($sid = $this->sessions[$evt->getEntity()->CID]) !== -1)
				if($this->arenas[$sid]->onMove($evt->getEntity()) === false)
					$evt->setCancelled(true);
		}
	}
	public function onInteract(PlayerInteractEvent $evt){
		if($evt->getPlayer()->getLevel()->getName() !==
				Builder::spleef()->getName() or !isset($this->sessions[$evt->getPlayer()->CID]))
			return;
		if(($sid = $this->sessions[$evt->getPlayer()->CID]) !== -1){
			if($this->arenas[$sid]->onInteract($evt) === false)
				$evt->setCancelled(true);
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
			$this->sessions[$player->CID] = $SID;
		}
		else{
			$player->sendMessage("You can't join this arena! Reason: $reason");
		}
	}
	public function quit($from, Player $player){
		$isTeam = count(explode(".", Hub::get()->getChannel($player))) === 5;
		Hub::get()->setChannel($player, $isTeam ? "legionpe.chat.spleef.".$this->hub->getDb($player)->get("team"):"legionpe.chat.spleef.public");
		$this->sessions[$player->CID] = -1;
	}
	public function getChance(Player $player){
		return $this->hub->config->get("spleef")["chances"][$this->hub->getRank($player)];
	}
	public function onJoinMg(Player $p){
		$this->sessions[$p->CID] = -1;
		$this->atchmts[$p->CID] = $p->addAttachment($this->hub, "legionpe.cmd.mg.spleef", true);
	}
	public function onQuitMg(Player $p){
		if(!isset($this->sessions[$p->CID])) return;
		if(($s = $this->sessions[$p->CID]) !== -1){
			$this->arenas[$s]->quit($p, "logout");
		}
		unset($this->sessions[$p->CID]);
		$p->removeAttachment($this->atchmts[$p->CID]);
		unset($this->atchmts[$p->CID]);
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
	public function isJoinable(){
		return true;
	}
	public function getStats(Player $player){
		return "W.I.P. feature!";
	}
	public static $instance = false;
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
}
