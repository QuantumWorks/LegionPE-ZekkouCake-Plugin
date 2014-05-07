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
			$this->lastLevels[$p->CID] = array(0, time());
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
		$this->tmpLogs[$b->x.",".$b->y.",".$b->z] = array($p->getDisplayName(), HubPlugin::get()->getTeam($p)->getTeam(), time()); // as lightweight as possible
	}
	public function onMove(Player $player){
		$new = $this->getLevel($player);
		$time = time();
		$old = $this->lastLevels[$player->CID];
		$ol = $old[0];
		if($ol !== $new){
			$player->sendMessage("You have fallen into level $new!");
			if($new - $ol > 1){
				$player->sendMessage("C-C-Combo! You have fallen for two levels in ".($time - $old[1])." second(s)!");
			}
			if($time - $old[1] <= 2){
				$this->stupidGuessHole($player, $new - $ol);
			}
		}
		$this->lastLevels[$player->CID] = array($new, $time);
		if($new !== $this->floors)
			return;
		$this->kick($player, "Falling out of the arena");
		$player->sendMessage("You lost! -2 points to your team!");
		HubPlugin::get()->getTeam($player)["points"] -= 2;
		$this->checkPlayers();
	}
	protected function stupidGuessHole(Player $player, $levels){
		$level = $this->getLevel($player);
		for($I = 0; $I < $levels; $I++){
			$x = (int) $player->x;
			$y = $this->floorCyls[$level - 1 - $I]->centre->y;
			$z = (int) $player->z;
			$w = $this->topCentre->level;
			$b = $w->getBlock(new Vector3($x, $y, $z));
			if($b->getID() !== 0){
				$b = $w->getBlock(new Vector3($x + 1, $y, $z));
				if($b->getID() !== 0){
					$b = $w->getBlock(new Vector3($x + 1, $y, $z + 1));
					if($b->getID() !== 0){
						$b = $w->getBlock(new Vector3($x, $y, $z + 1));
						if($b->getID() !== 0)
							continue;
					}
				}
			}
			$log = $this->tmpLog[$b->x.",".$b->y.",".$b->z];
			$name = $log[0];
			$team = $log[1];
			$time = $log[2];
			if(($diff = time() - $time) > 15)
				continue;
			$player->sendMessage("Our stupid spleef-hole guesser thinks that you fell in a hole mined by $name $diff second(s) ago!");
			if($name === $player->username){
				$player->sendMessage("Minecraft basic rules #1: Never mine directly below yourself.");
				continue;
			}
			if($team === HubPlugin::get()->getTeam($player)->getTeam()){
				$player->sendMessage("Your teammate betrayed you! xD forgive him though. He must have been careless.");
				Player::get($name)->sendMessage("Hey, why did you dig a hole for your teammate ".$player->getDisplayName()." to fall into?");
			}
			else{
				$player->sendMessage("You mined a hole to fall ".$player->getDisplayName().". 2 team points to you!");
				HubPlugin::get()->getTeam($team)["points"] += 2;
			}
		}
	}
	protected function getLevel(Vector3 $vector){
		$y = $vector->y;
		$l = 0;
		foreach($this->floorCyls as $cyl){
			if((int)$cyl->centre->y > $y)
				$l++;
			else break;
		}
		return $l;
	}
	protected function checkPlayers(){
		foreach($this->players as $p){
		}
	}
}
