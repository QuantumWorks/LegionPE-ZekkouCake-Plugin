<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\CallbackEventExe as EvtExe;
use pemapmodder\utils\FileUtils;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\level\Level;

class Main implements MgMain, Listener{
	protected $current = null;
	public function __construct(){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$this->initialize();
	}
	protected function initialize(){
		FileUtils::copy(RawLocs::basePath(), RawLocs::worldPath());
		// $this->current = new Game($this->server->getLevel(RawLocs::worldName()));
		$this->hub->addDisableListener(array($this, "finalize"));
	}
	public function onJoinMg(Player $p){
		$this->current->join($p);
	}
	public function onQuitMg(Player $p){
		$this->current->quit($p);
	}
	public function getName(){
		return "CTF";
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
	public function isJoinable(){
		if(isset($this->current) and $this->current instanceof Game){
			return $this->current->join($p);
		}
		return "Not started";
	}
	public function getStats(Player $player){
		return "W.I.P. feature!";
	}
	public function finalize(){
		if(isset($this->current) and $this->current instanceof Game)
			$this->current->finalize("server stop");
	}
	public function getGame(){
		return $this->current;
	}
	public function endGame(){
		$this->current = null;
	}
	public static $ctf = false;
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
}
