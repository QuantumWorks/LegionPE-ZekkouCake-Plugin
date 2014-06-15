<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\Hub;
use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;
use pemapmodder\utils\FileUtils;
use pocketmine\Player;
use pocketmine\Server;

class Main extends MgMain{
	const STATE_REINITIALIZING = 0;
	const STATE_WAITING = 1;
	const STATE_PLAYING = 2;
	const STATE_FINALIZING = 3;
	private $state = self::STATE_REINITIALIZING;
	private $players = [];
	public function __construct(){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
	}
	public function wait(){
		@FileUtils::del(Rawlocs::worldPath());
		FileUtils::copy(RawLocs::basePath(), RawLocs::worldPath());
		$this->server->loadLevel(RawLocs::worldName());
		self::STATE_WAITING;
	}
	public function finalize(){
		$this->server->unloadLevel($this->server->getLevel(RawLocs::worldName()));
	}
	public function end(){
		foreach($this->getAllPlayers() as $p){
			$this->kick($p, "Match ending...");
		}
	}
	public function kick(Player $player, $reason = "No reason"){
		$player->sendMessage("You have been kicked from CTF. Reason: $reason");
		Hub::get()->onQuitCmd($player, []);
	}
	public function onJoinMg(Player $p){
		$t = $this->hub->getDb($p)->get("team");
		$this->players[$t][$p->getID()] = $p;
	}
	public function onQuitMg(Player $p){
		$t = $this->hub->getDb($p)->get("team");
		unset($this->players[$t][$p->getID()]);
	}
	/**
	 * @return Player[]
	 */
	public function getAllPlayers(){
		$out = [];
		for($i = 0; $i < 4; $i++){
			/** @var Player $p */
			foreach($this->players[$i] as $p){
				$out[$p->getID()] = $p;
			}
		}
		return $out;
	}
	public function broadcastMessage($msg){
		foreach($this->getAllPlayers() as $p){
			$p->sendMessage($msg);
		}
	}
	public function isJoinable(Player $player, $t){
		// TODO
	}
	public function getState(){
		return $this->state;
	}
	public function getName(){
		return "Capture The Flag";
	}
	public function getSessionId(){
		return HubPlugin::CTF;
	}
	public function getSpawn(Player $p, $TID){
		return RawLocs::pSpawn($TID);
	}
	public function getDefaultChatChannel(Player $p, $TID){
		return "legionpe.chat.ctf.$TID";
	}
	public function getStats(Player $player, array $args = []){
		return "W.I.P. feature!"; // TODO
	}
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
}
