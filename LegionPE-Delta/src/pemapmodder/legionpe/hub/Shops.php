<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\mgs\MgMain;

use pocketmine\Player;
use pocketmine\Server;

class Shops implements MgMain{
	public function __construct(){
		$this->server = Server::getInstance();
		$this->hub = HubPlugin::get();
	}
	public function onJoinMg(Player $player){
	}
	public function onQuitMg(Player $player){
	}
	public function getName(){
		return "LegionPE Shops";
	}
	public function getSessionId(){
		return HubPlugin::SHOP;
	}
	public function getSpawn(Player $player, $TID){
		return RawLocs::shopSpawn();
	}
	public function getDefaultChatChannel(Player $Player, $TID){
		return "legionpe.chat.shops";
	}
	public function isJoinable(){
		return true;
	}
	public function getStats(Player $player){
		return "This will show your coins. W.I.P. Sorry.";
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
}
