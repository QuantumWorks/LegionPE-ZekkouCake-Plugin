<?php

namespace legionpe;

use legionpe\minigames\ctf\CTF;
use legionpe\minigames\kitpvp\KitPvp;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase{
	const CHAT_CHANNEL_ROOT = "legionpe.chat";
	/**
	 * @var Sessioner
	 */
	private $sessioner;
	/** @var ChatManager */
	private $chatMgr;
	/**
	 * @var Minigame[]
	 */
	private $minigames = [];
	/**
	 * @var Config
	 */
	private $genConfig;
	public function onEnable(){
		@mkdir($this->getDataFolder()."players/");
		$this->sessioner = new Sessioner($this);
		$this->chatMgr = new ChatManager($this);
		$this->registerMinigame(new CTF($this));
		$this->registerMinigame(new KitPvp($this));
		$this->saveResource("generals.json");
		$this->genConfig = new Config($this->getDataFolder()."general.json", Config::JSON);
		$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask($this->sessioner, 20, 20);
	}
	public function registerMinigame(Minigame $mg){
		$this->minigames[$mg->getName()] = $mg;
	}
	/**
	 * @return Sessioner
	 */
	public function getSessioner(){
		return $this->sessioner;
	}
	/**
	 * @return Minigame[]
	 */
	public function getMinigames(){
		return $this->minigames;
	}
	/**
	 * @return Config
	 */
	public function getGenConfig(){
		return $this->genConfig;
	}
	public function getMinigameByPos(Position $pos){
		return $this->getMinigameByLevel($pos->getLevel());
	}
	/**
	 * @param Level $level
	 * @return self|Minigame
	 */
	public function getMinigameByLevel(Level $level){
		foreach($this->minigames as $minigame){
			if($minigame->ownLevel($level->getName())){
				return $minigame;
			}
		}
		return $this;
	}
	/**
	 * @return ChatManager
	 */
	public function getChatManager(){
		return $this->chatMgr;
	}
}
