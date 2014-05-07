<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\hub\Team;

use pemapmodder\utils\DummyPlugin as Utils;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class Arena extends PluginTask{
	public $hub, $id, $centre, $radius, $height, $floors;
	protected $gfloor, $pfloor, $pwall, $pceil;
	protected $prestartTicks = -1, $scheduleTicks = -1, $runtimeTicks = -1;
	public $status = 0, $players = array(), $preps;
	protected $tmpLog = array(), $lastLevels = array(), $floorCyls = array();
	public function __construct($id, Position $topCentre, $radius, $height, $floors, $players,
			Block $floor, Block $pfloor, Block $pwall, Block $pceil){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$this->main = Main::get();
		$this->id = $id;
		$this->centre = $topCentre;
		$this->radius = $radius;
		$this->height = $height;
		$this->floors = $floors;
		$this->gfloor = $floor;
		$this->pfloor = $pfloor;
		$this->pwall = $pwall;
		$this->pceil = $pceil;
		$this->pcnt = $players;
		$this->server->getScheduler()->scheduleRepeatingTask($this, 1);
		$this->refresh();
	}
	protected function refresh(){
		$this->tmpLog = array();
		$this->players = array();
		$this->build($this->pcnt);
	}
	protected function reloop(){
		if(count($this->players) > 0){
			console("[WARNING] SpleefArena {$this->id} was not properly closed. Players are remaining.");
			foreach($this->players as $p)
				$this->kick($p, "restart");
		}
		$this->refresh();
		$this->status = 0;
		foreach(Utils::getTile(Builder::signs($this->id)->getBlockMap()) as $tile){
			$tile->setText("0 / {$this->pcnt}", "JOINABLE!", "Join Arena {$this->id}");
		}
	}
	public function isJoinable(){
		if($this->status === 1)
			return "Arena already started.";
		if(count($this->players) < $this->pcnt)
			return "Arena full.";
		return true;
	}
	public function kick(Player $player, $reason = "Unknown reason"){
		$this->quit($player, "Kick from arena for $reason");
	}
	public function cntPlayers(){
		return count($this->players);
	}
	public function maxPlayers(){
		return count($this->preps);
	}
	protected function build($cnt){
		$this->preps = Builder::build($this->centre, $this->radius, $this->gfloor, $this->floors, $this->height, $cnt, $this->pfloor, $this->pwall, $this->pceil, $floors);
		$this->floorCyls = $floors;
	}
	public function join(Player $player){
		if(!$this->isJoinable())
			return false;
		$this->players[$player->CID] = $player;
		$this->broadcast($player->getDisplayName()." has joined this arena.");
		$player->sendMessage("You have joined arena {$this->id}!");
		$player->sendMessage("There are now ".count($this->players)." players in this arena, ".($this->pcnt - count($this->players))." more needed.");
		if(count($this->players) >= $this->pcnt){
			$this->prestart();
		}
		elseif(count($this->players) === 2 and $this->scheduleTicks <= 0){
			$this->broadcast("60 seconds until match starts!");
			$this->scheduleTicks = 20 * 60;
		}
		return true;
	}
	public function quit(Player $player, $reason = "Unknown reason"){
		unset($this->players[$player->CID]);
		$this->broadcast($player->getDisplayName()." left. Reason: $reason.");
	}
	public function broadcast($message, $ret = null){
		foreach($this->players as $p)
			$p->sendMessage($message);
		return $ret;
	}
	protected function prestart(){ // schedule the starting
		$this->prestartTicks = 201;
	}
	public function onRun($ticks){ // scheduled task per tick
		$this->scheduleTicks--;
		if($this->scheduleTicks % (20 * 10) === 0){
			$this->broadcast(($this->scheduleTicks / 20)." seconds before match starts!");
		}
		$this->prestartTicks--;
		if($this->prestartTicks > 20 and $this->prestartTicks % 20 === 0){
			$this->broadcast(($this->prestartTicks / 20)." seconds before match starts!");
		}
		elseif($this->prestartTicks === 20)
			$this->broadcast("1 second before match starts!");
		elseif($this->prestartTicks === 0){
			$this->start();
		}
		$this->runtimeTicks--;
		if($this->runtimeTicks === 1){
			$this->end("Time's up!");
			$this->runtimeTicks = -1;
		}
	}
	protected function start(){
		$this->runtimeTicks = 20 * 60 * 3;
		foreach($this->players as $p)
			$this->lastLevels[$p->CID] = 0;
	}
	protected function end($reason){
		$this->broadcast("The match ended. Reason: $reason.");
	}
	public function onInteract($event){
		$p = $event->getPlayer();
		$b = $event->getBlock();
		foreach($this->floorCyls as $cs){
			if($cs->isInside($b)){
				$yes = true;
				break;
			}
		}
		if(!isset($yes)){
			$event->setCancelled(true);
			return;
		}
		if(mt_rand(1, 100) <= Main::get()->getChance($p)){
			$b->level->setBlock($b, Block::get(0));
		}
		$this->tmpLogs[$b->x.",".$b->y.",".$b->z] = array($p->getDisplayName(), HubPlugin::get()->getTeam, time()); // as lightweight as possible
	}
	public function onMove(Player $player){
		$this->getLevel($player);
	}
	protected function getLevel(Vector3 $vector){
		$y = $vector->y;
		foreach($this->floorCyls as $cyl){
			
		}
	}
}
