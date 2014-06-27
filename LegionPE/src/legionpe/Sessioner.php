<?php

namespace legionpe;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;

class Sessioner extends PluginTask implements Listener{
	const CATEGORY   = 0b11110000;
	const DETAILS    = 0b00001111;
	const REGISTER   = 0b00010000;
	const LOGIN      = 0b00100000;
	const IN_GAME    = 0b01000000;
	const REGISTER_0 = 0b00010001;
	const REGISTER_1 = 0b00010010;
	const REGISTER_2 = 0b00010011;
	const LOGIN_0    = 0b00100001;
	const LOGIN_1    = 0b00100010;
	const LOGIN_2    = 0b00100011;
	const LOGIN_3    = 0b00100100;
	const LOGIN_4    = 0b00100101;
	const LOGIN_MAX  = 0b00100110;
	const IG_SPAWN   = 0b01000000;
	const IG_PVP     = 0b01000001; // KitPvP
	const IG_PK      = 0b01000010; // Parkour
	const IG_SPLEEF  = 0b01000011; // MultiSpleef
	const IG_CTF     = 0b01000100; // Capture The Flag
	const IG_IFT     = 0b01000101; // Infected

	private $main;
	/** @var int[] */
	private $sessions = [];
	private $tmpPws = [];
	/** @var \pocketmine\permission\PermissionAttachment[] */
	private $atts = [];
	/** @var PlayerConfig[] */
	private $dbs = [];
	public function __construct(Main $main){
		$this->main = $main;
		$this->main->getServer()->getPluginManager()->registerEvents($this, $main);
		parent::__construct($main);
	}
	/**
	 * @param PlayerJoinEvent $event
	 * @priority LOWEST
	 */
	public function onJoin(PlayerJoinEvent $event){
		$event->setJoinMessage("");
		$p = $event->getPlayer();
		$this->atts[$p->getID()] = $p->addAttachment($this->main);
		$db = $this->getDb($p);
		$hashed = $db->getPassword();
		$p->sendMessage("================");
		if($hashed === false){ // register
			$this->sessions[$p->getID()] = self::REGISTER_0;
			$p->sendMessage("Welcome to LegionPE!\nTo protect your account, we have started a registration wizard (RegWiz) for you.");
			$p->sendMessage("================");
			$p->sendMessage("[RegWiz] Please type your password into chat and send it. No one else will be able to see it.");
		}
		else{
			$p->sendMessage("Welcome back to LegionPE!");
			if($db->getAuthIP() === true and $db->hasIP($p->getAddress())){ // IP auth
				$this->authenticate($p, "AllIPsAuth");
			}
			elseif($db->getAuthIP() === $p->getAddress()){
				$this->authenticate($p, "LastIPAuth");
			}
			else{ // password auth
				$this->sessions[$p->getID()] = self::LOGIN_0;
				$p->sendMessage("Please type your password directly in chat to authenticate.");
			}
		}
	}
	/**
	 * @param PlayerCommandPreprocessEvent $event
	 * @priority LOWEST
	 */
	public function onPreCmd(PlayerCommandPreprocessEvent $event){
		$msg = $event->getMessage();
		$session = $this->getSession($p = $event->getPlayer());
		$details = $session & self::DETAILS;
		$db = $this->getDb($p);
		switch($category = $session & self::CATEGORY){
			case self::REGISTER:
				switch($details){
					case 1:
						$this->tmpPws[$p->getID()] = $msg;
						$p->sendMessage("[RegWiz] Good job. Now, please repeat the password you typed to ensure you made no mistakes.");
						$this->sessions[$p->getID()]++;
						break;
					case 2:
						if($this->tmpPws[$p->getID()] === $msg){ // password matches
							$p->sendMessage("[RegWiz] The password has been matched! :)");
							$db->savePassword($msg);
							$p->sendMessage("================");
							$p->sendMessage("[RegWiz] To make it convenient for you to login next time, you can choose to authenticate by your IP.");
							$p->sendMessage("[RegWiz] Your IP is related to the network (e.g. your Wi-Fi) you are connecting to.");
							$p->sendMessage("[RegWiz] People cannot connect with your IP unless they use the same network you are using.");
							$p->sendMessage("================");
							$p->sendMessage("[RegWiz] Do you want to enable IP authentication?");
							$p->sendMessage("If yes, type ".TextFormat::AQUA."\"yes\"".TextFormat::RESET." in chat to authenticate by the last IP you have authenticated with. Otherwise, type".TextFormat::AQUA." \"no\"".TextFormat::RESET." in chat. Type ".TextFormat::AQUA."\"all\"".TextFormat::RESET." if you wish every IP you connect with to be able to auto-authenticate.");
							$this->sessions[$p->getID()]++;
						}
						else{
							$p->sendMessage("[RegWiz] Password doesn't match!\n[RegWiz] Your registration wizard has been reset. Please type your password directly into chat.");
							$this->sessions[$p->getID()]--;
						}
						break;
					case 3:
						if(strtolower($msg) === "all"){
							$db->setAuthIP(true);
						}
						elseif($this->identifyClientBoolean($msg)){
							$p->sendMessage("[RegWiz] Your IP authentication has been enabled.");
							$db->setAuthIP($p->getAddress());
						}
						else{
							$p->sendMessage("[RegWiz] Your IP authentication has been disabled.");
							$db->setAuthIP(false);
						}
						$p->sendMessage("[LegionPE] You have successfully registered!");
						$this->authenticate($p, "RegWiz");
						break;
				}
				break;
			case self::LOGIN:
				if($db->matchPassword($msg)){
					$p->sendMessage("The password matches :)");
					$this->authenticate($p, "PasswordAuth");
				}
				else{
					$this->sessions[$p->getID()]++;
					$chances = self::LOGIN_MAX - $this->getSession($p);
					$p->sendMessage("You have $chances more chance(s) to login before you are kicked for failing to login.");
					if($chances === 0){
						$p->close("failure to authenticate in 5 attempts", "authentication failure (chances used up)");
					}
				}
				break;
			case self::IN_GAME:
				if($db->matchPassword($msg)){
					$event->setCancelled(true);
					$p->sendMessage("Never speak your own password loudly to others!");
				}
				break;
		}
	}
	private function identifyClientBoolean($msg){
		if(in_array(strtolower($msg), ["on", "true", "yes", "ok", "okay", "y", "t", "i", "1", "sure"])){
			return true;
		}
		return false;
	}
	private function authenticate(Player $player, $method){
		$player->sendMessage("You have been authenticated by $method.");
		$this->main->getMinigameByPos($player);
		if($this->getDb($player)->getTeam() === false){
			$player->teleport(Geog::spawn());
			$player->sendMessage("Please select a team. This team will be the team you play as in some games.");
		}
	}
	public function onQuit(PlayerQuitEvent $event){
		if(isset($this->atts[$id = $event->getPlayer()->getID()])){
			$event->getPlayer()->removeAttachment($this->atts[$id]);
			unset($this->atts[$id]);
		}
		$this->closeDb($event->getPlayer());
	}
	public function getSession(Player $player){
		return $this->sessions[$player->getID()];
	}
	public function getDb(Player $player){
		if(!isset($this->dbs[$player->getID()])){
			$this->openDb($player);
		}
		return $this->dbs[$player->getID()];
	}
	private function openDb(Player $player){
		$this->dbs[$player->getID()] = new PlayerConfig($player, $this->main);
	}
	private function closeDb(Player $player){
		$this->getDb($player)->save();
		unset($this->dbs[$player->getID()]);
	}
	public function getAttachment(Player $player){
		return $this->atts[$player->getID()];
	}
	public function onRun($ticks){
		foreach($this->main->getServer()->getOnlinePlayers() as $p){
			if(($this->getSession($p) & self::CATEGORY) === self::LOGIN and (time() - $this->getDb($p)->getLastjoin()) >= 120){
				$p->kick("Authentication (PasswordAuth) timeout");
			}
		}
	}
}
