<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\Hub;
use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\hub\Team;
use pemapmodder\legionpe\mgs\MgMain;
use pemapmodder\utils\FileUtils;
use pocketmine\network\protocol\PlayerArmorEquipmentPacket;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\network\protocol\DataPacket;

class Main extends MgMain{
	const STATE_REINITIALIZING = 0;
	const STATE_WAITING = 1;
	const STATE_PLAYING = 2;
	const STATE_FINALIZING = 3;
	private $state = self::STATE_REINITIALIZING;
	private $players = [];
	/** @var RawLocs */
	private $builder;
	public function __construct(){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$parent = DefaultPermissions::registerPermission(new Permission("legionpe.mg.ctf", "Allow doing everything in CTF", Permission::DEFAULT_FALSE));
		$team = DefaultPermissions::registerPermission(new Permission("legionpe.mg.ctf.team", "Identity as all teams - NEVER give this to anyone", Permission::DEFAULT_FALSE), $parent);
		for($i = 0; $i < 4; $i++){
			DefaultPermissions::registerPermission(new Permission("legionpe.mg.ctf.$i", "Identity as team $i", Permission::DEFAULT_FALSE), $team);
		}
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
	public function start(){
		$this->state = self::STATE_PLAYING;
		/** @var Player[] $ps */
		foreach($this->players as $team => $ps){
			/** @var PlayerArmorEquipmentPacket $pk */
			$pk = new PlayerArmorEquipmentPacket;
			$pk->slots = Team::get($team)->getArmor();
			foreach($ps as $p){
				$tempPk = clone $pk;
				$tempPk->eid = $p->getID();
				$this->broadcastPacket($tempPk, $p);
			}
		}
		$this->broadcastMessage("The tournament has started!");
		$this->builder = new Rawlocs;
		$this->builder->init();
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
		$this->hub->getPermAtt($p)->setPermission("legionpe.mg.ctf.$t", true);
		if(min(count($this->players[0]), count($this->players[1]), count($this->players[2]), count($this->players[3])) === 2){
			$this->start();
		}
	}
	public function onQuitMg(Player $p){
		$t = $this->hub->getDb($p)->get("team");
		unset($this->players[$t][$p->getID()]);
		$this->hub->getPermAtt($p)->setPermission("legionpe.mg.ctf.$t", false);
		///////////////////////////////////////
		// above and below, which is better? //
		///////////////////////////////////////
		$this->hub->getPermAtt($p)->unsetPermission("legionpe.mg.ctf.$t");
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
	/**
	 * @param DataPacket $packet
	 * @param bool|Player|int $exception
	 */
	public function broadcastPacket(DataPacket $packet, $exception = false){
		if($exception instanceof Player){
			foreach($this->getAllPlayers() as $p){
				if($p->getID() !== $exception->getID()){
					$p->dataPacket($packet);
				}
			}
		}
		elseif(is_int($exception)){
			foreach($this->players as $team => $players){
				if($team === $exception) continue;
				/** @var Player $p */
				foreach($players as $p){
					$p->dataPacket($packet);
				}
			}
		}
		else{
			foreach($this->getAllPlayers() as $p){
				$p->dataPacket($packet);
			}
		}
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
	public function getPermission(){
		return "legionpe.mg.ctf";
	}
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
}
