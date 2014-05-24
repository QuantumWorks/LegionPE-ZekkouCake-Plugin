<?php

namespace pemapmodder\smg;

use pocketmine\Player;
use pocketmine\Server;

class Penalty extends PluginTask{
	public static $pid = 0;
	public $cancelled = false;
	public $id;
	public static function add(Player $issuer, Player $target, $flags, $seconds = 15, $extraData = false){
		$penalty = new static($issuer, $target, $flags, $seconds, $extraData)
		Server::getInstance()->getScheduler()->scheduleDelayedTask($penalty, $seconds * 15);
		Main::get()->penalties[$penalty->id] = $penalty;
	}
	public function __construct(Player $issuer, Player $target, $reasons, $seconds = 15, $extraData = false){
		parent::__construct(Main::get());
		$this->server = Server::getInstance();
		$this->id = self::$pid++;
		$this->inetAddress = $target->getAddress();
		$target->sendMessage("You are given a penalty by ".Main::get()->getRank($issuer)." ".$issuer->getDisplayName()." for the following reasons:");
		$target->sendMessage(implode(", ", Main::get()->evalFlags($reasons)[0]));
		if($extraData){
			$target->sendMessage("Extra information from the penalty issuer: $extraData");
		}
		$target->sendMessage("You can raise an objection to this ban in 15 seconds.");
		$target->sendMessage("If your objection is not accepted, you can create a ban appeal on penalty ID {$this->id} on the forums.");
		$this->action = Main::get()->evalFlags($reasons)[1];
		$target->sendMessage("This penalty gives you {$this->action} ban points.");
	}
	public function onRun($ticks){
		Main::get()->list->warn($this->inetAddress, $this->action);
	}
	public static function get($pid){
		return @Main::get()->penalties[$pid];
	}
}
