<?php

namespace pemapmodder\smg;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\event\EventPriority as EP;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase as ParentClass;

class Main extends ParentClass implements Listener{
	const PERMANENT = 0;
	
	const IMPROPER_CHAT	= 0b00000111;
	const SPAM			= 0b00000001;
	const SWEAR			= 0b00000010;
	const STAFF_IMPOSE	= 0b00000100;
	const MOD_USE		= 0b11110000;
	const CLIMB_MOD		= 0b00010000;
	const FLY_MOD		= 0b00100000;
	const JUMP_MOD		= 0b01000000;
	const SPRINT_MOD	= 0b10000000;
	
	public function onEnable(){
		$this->actionLogger = new ActionLogger($this);
	}
	public function onCommand(Issuer $isr, Command $cmd, $lbl, array $args){
		switch($cmd){
			case "report":
				if(!isset($args[1])){
					return false;
				}
				$player = array_shift($args);
				$this->submitReport(
		}
	}
	public function banIP($hours = self::PERMANENT, $reason){
		$this->getServer()->get;
	}
}
