<?php

namespace pemapmodder\smg;

use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\PluginTask;

class Penalty extends PluginTask{
	public static $pid = 0;
	public $cancelled = false;
	public $id;
	/**
	 * @var \pocketmine\Player penalty issuer
	 */
	private $issuer;
	/**
	 * @var \pocketmine\IPlayer penalty receiver
	 */
	private $target;
	/**
	 * @var string[]
	 */
	private $reasons;
	/**
	 * @var bool
	 */
	private $extraData;
	public static function add(Player $issuer, IPlayer $target, $flags, $seconds = 15, $extraData = false){
		$penalty = new static($issuer, $target, $flags, $extraData);
		Server::getInstance()->getScheduler()->scheduleDelayedTask($penalty, $seconds * 20);
		Main::get()->penalties[$penalty->id] = $penalty;
		return $penalty;
	}
	public function __construct(Player $issuer, IPlayer $target, $reasons, $extraData = false){
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
		$this->issuer = $issuer;
		$this->target = $target;
		$this->reasons = $reasons;
		$this->extraData = $extraData;
	}
	public function cancel(){
		$this->cancelled = true;
	}
	public function onRun($ticks){
		if($this->cancelled !== true){
			Main::get()->list->warn($this->inetAddress, $this->action);
			$this->cancel();
		}
	}
	public function getPoints(){
		return $this->action;
	}
	public static function get($pid){
		return isset(Main::get()->penalties[$pid]) ? Main::get()->penalties[$pid]:false;
	}
}
