<?php

namespace pemapmodder\smg;

use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\command\ConsoleCommandSender as Console;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\plugin\EventExecutor;

class ActionLogger implements EventExecutor, Listener{
	const CHAT_NO = "no chat";
	const CHAT_CHAT = "norm chat";
	const CHAT_ME = "/me chat";
	const CHAT_SAY = "/say chat";
	public static $MAX_CHAT_LOG = 50;
	public static $MAX_MOTION_LOG = 25;
	public $chatLog = [];
	public $motionLog = [];
	public function __construct(Main $plugin){
		$this->main = $plugin;
		$this->server = Server::getInstance();
		$this->pm = $this->server->getPluginManager();
		$this->init();
		$console = new Console;
		// for($i = 0; $i < self::$MAX_CHAT_LOG; $i++){
			// $this->chatLog[] = [$console, "server-downtime", self::CHAT_NO, time()];
		// }
	}
	protected function init(){
		$pm = $this->pm;
		$pm->registerEvents($this, $this->main);
//		foreach([["player", "chat"], ["entity", "move"], ["entity", "motion"], ["player", "commandPreprocess"]] as $event){
//			$pm->registerEvent("pocketmine\\event\\".$event[0]."\\".ucfirst($event[0]).ucfirst($event[1])."Event",
//					$this, EventPriority::MONITOR, $this, $this->main, true);
//		}
	}
	public function onChat(PlayerChatEvent $event){
		$this->logChat($event->getPlayer(), $event->getMessage(), self::CHAT_CHAT);
	}
	public function onPreCmd(PlayerCommandPreprocessEvent $event){
		$cmd = strstr($event->getMessage(), " ", true);
		if(in_array($cmd, ["/me", "/say"])){
			$this->logChat($event->getPlayer(), substr(strstr($event->getMessage(), " "), 1), $cmd === "/say" ? self::CHAT_SAY:self::CHAT_ME);
		}
	}
	public function onMove(EntityMoveEvent $event){
	}
	public function onMotion(EntityMotionEvent $event){
		$e = $event->getEntity();
		if(!($e instanceof Player)){
			return;
		}
		if($e->getGamemode() & 0x01){
			return;
		}
		$this->logMotion($e, $event->getVector());
	}
	public function logChat(Issuer $speaker, $message, $type){
		$this->chatLog[] = [$speaker, $message, $type, time()];
		while(count($this->chatLog) > self::$MAX_CHAT_LOG){
			array_shift($this->chatLog);
		}
		return true;
	}
	public function logMotion(Player $player, Vector3 $vectors){
		$this->motionLog[$player->getID()][] = [$vectors, microtime(true)];
	}
	public function exportChatBacklog(){
		$output = "";
		$lastTime = $this->chatLog[0][3];
		foreach($this->chatLog as $data){
			$timeDiff= $data[3] - $lastTime;
			$name = $data[0]->getName();
			$message = $data[1];
			$type = $data[2];
			$lastTime = $data[3];
			$output .= "+$timeDiff s: $name> $message [$type]\n";
		}
		return $output;
	}
	public function exportMotionBacklog($CID){
		$data = $this->motionLog[$CID];
		$output = "";
		$lastTime = $data[0][1];
		foreach($data as $dat){
			$x = $dat[0]->x;
			$y = $dat[0]->y;
			$z = $dat[0]->z;
			$time = round($dat[1] - $lastTime, 2);
			$output .= "Move ($x, $y, $z) for $time s\n";
		}
		return $output;
	}
}
