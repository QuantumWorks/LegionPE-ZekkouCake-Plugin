<?php

namespace pemapmodder\legionpe\mgs\infected;

use pemapmodder\legionpe\geog\Position;
use pemapmodder\legionpe\geog\RawLocs;
use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;

class Main extends MgMain implements Listener{
	/** @var HubPlugin */
	private $hub;
	/** @var Server */
	private $server;
	public function __cosntruct(){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$this->server->getPluginManager()->registerEvents($this, $this->hub);
	}
	public function getName(){
		return "Infected";
	}
	public function getPermission(){
		return "legionpe.mgs.infected";
	}
	public function getDefaultChatChannel(Player $player, $teamID){
		return "legionpe.chat.infected.public";
	}
	public function onJoinMg(Player $player){
		// TODO
	}
	public function onQuitMg(Player $player){
		// TODO
	}
	public function getSessionId(){
		return HubPlugin::INFECTED;
	}
	public function getSpawn(Player $player, $teamID){
		return new Position(0x70d0, 0x70d0, 0x70d0, RawLocs::getLevel("TODO")); // 0x70d0 is the leet for TODO
	}
	public function isJoinable(Player $player, $teamID){
		return true; // TODO
	}
	public function getStats(Player $player, array $args = []){

	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static;
	}
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
}
