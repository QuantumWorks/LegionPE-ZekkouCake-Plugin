<?php

namespace pemapmodder\smg;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\event\EventPriority as EP;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase as ParentClass;
use pocketmine\utils\Config;

class Main extends ParentClass implements Listener{
	const REGPEN	= 1;
	const REGBAN	= 3;
	const PERMBAN	= 15;
	
	const SPAM			= 0b000000000001; // 1
	const HARRASS		= 0b000000000010; // 2
	const SWEAR			= 0b000000000100; // 4
	const STAFF_IMPOSE	= 0b000000001000; // 8
	const CLIMB_MOD		= 0b000000010000; // 16
	const FLY_MOD		= 0b000000100000; // 32
	const JUMP_MOD		= 0b000001000000; // 64
	const SPRINT_MOD	= 0b000010000000; // 128
	const GLITCH_USE	= 0b000100000000; // 256
	const IMPROPER_CHAT	= 0b001000000000; // 512
	const MOD_USE		= 0b000011110000; // 235
	
	const ADMIN = "admin";
	const MOD_GLOB = "global moderator";
	const MOD_SEC = "sectional moderator";
	const NORM = "player";
	
	public $list;
	public $penalties = [];
	
	public function onEnable(){
		$this->actionLogger = new ActionLogger($this);
		$this->list = new BanList($this->getDataFolder()."ban-list.json");
		$this->ranks = new Config($this->getDataFolder()."config.yml", Config::YAML, [
			"admin" => ["lambo", "spyduck", "pemapmodder"],
			"mods" => [
				"global" => ["sean_m"],
				"sectional" => [
					"trollofmc" => ["world_pvp", "world_spleef"],
				]
			]
		]);
	}
	public function getRank(Player $player){
		$name = strtolower($player->getName());
		foreach($this->ranks->get("admin") as $member){
			if(strtolower($member) === $name){
				return self::ADMIN;
			}
		}
		foreach($this->ranks->get("mods")["global"] as $member){
			if(strtolower($member) === $name){
				return self::MOD_GLOB;
			}
		}
		foreach($this->ranks->get("mods")["sectional"] as $mod => $worlds){
			if($mod === $name){
				return self::MOD_SEC;
			}
		}
		return self::NORM;
	}
	public function onCommand(Issuer $isr, Command $cmd, $lbl, array $args){
		switch($cmd){
			case "report":
				if(!isset($args[1])){
					return false;
				}
				$player = array_shift($args);
				$details = implode(" ", $args);
				break;
		}
	}
	public function evalFlags($flags){
		$out = [];
		// reasons
		if($flags & self::SPAM){
			$out[] = "spam";
		}
		if($flags & self::HARRASS){
			$out[] = "harrassing other players";
		}
		if($flags & self::SWEAR){
			$out[] = "swearing";
		}
		if($flags & self::STAFF_IMPOSE){
			$out[] = "staff imposement";
		}
		if($flags & self:CLIMB_MOD){
			$out[] = "climbing mod usage";
		}
		if($flags & self::FLY_MOD){
			$out[] = "flying mod usage";
		}
		if($flags & self::JUMP_MOD){
			$out[] = "super jump mod usage";
		}
		if($flags & self::SPRINT_MOD){
			$out[] = "using walk faster/sprinting mod";
		}
		if($flags & self::GLITCH_USE){
			$out[] = "improper usage of glitches or bugs";
		}
		if($flags & self::IMPROPER_CHAT){
			$out[] = "improper chat behaviours";
		}
		// actions
		$actions = 0;
		if($flags & self::MOD_USE){
			$actions += self::REGBAN;
		}
		if($flags & self::SPAM){
			$action += self::REGBAN;
		}
		if($flags & self::HARRASS){
			$action += self::REGPEN;
		}
		if($flags & self::SWEAR){
			$actions += self::REGPEN;
		}
		if($flags & self::STAFF_IMPOSE){
			$actions += self::REGBAN;
		}
		return [$out, $actions];
	}
	public static function get(){
		return Server::getInstance()->getPluginManager()->getPlugin("ServerMiscellaneousGenerals");
	}
}
