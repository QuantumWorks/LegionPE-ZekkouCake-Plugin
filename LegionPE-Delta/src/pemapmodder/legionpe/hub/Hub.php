<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\legionpe\mgs\MgMain;
use pemapmodder\legionpe\mgs\pk\Parkour;
use pemapmodder\legionpe\mgs\pvp\Pvp;
use pemapmodder\legionpe\mgs\spleef\Main as Spleef;
use pemapmodder\legionpe\mgs\ctf\Main as CTF;

use pemapmodder\utils\CallbackPluginTask;
use pocketmine\command\CommandSender;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor as CmdExe;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\permission\DefaultPermissions as DP;
use pocketmine\permission\Permission as Perm;

/**
 * Responsible for portals, chat and coins
 */
class Hub implements CmdExe, Listener{
	public $server;
	public $teleports = array();
	/** @var CallbackPluginTask[] */
	public $mutes = [];
	protected $channels;
	public static function defaultChannels(){
		$r = array(
			"legionpe.chat.public",
			"legionpe.chat.mandatory",
			"legionpe.chat.team.TID",
			"legionpe.chat.shops",
			"legionpe.chat.pvp.public",
			"legionpe.chat.pvp.TID",
			"legionpe.chat.pk.public",
			"legionpe.chat.pk.TID",
			"legionpe.chat.ctf.public",
			"legionpe.chat.ctf.TID",
			"legionpe.chat.spleef.public",
			"legionpe.chat.spleef.tTID",
			"legionpe.chat.spleef.sSID",
			"legionpe.chat.spleef.sSID.tTID",
		);
		$out = array();
		foreach($r as $ch){
			if(strpos($ch, "SID") === false){
				$out[] = $ch;
				continue;
			}
			for($i = 0; $i < 4; $i++){
				$out[] = str_replace("TID", "$i", $ch);
			}
		}
		$r = $out;
		$out = array();
		foreach($r as $ch){
			if(strpos($ch, "TID") === false){
				$out[] = $ch;
				continue;
			}
			for($i = 0; $i < 4; $i++){
				$out[] = str_replace("TID", "$i", $ch);
			}
		}
		// var_export($out);
		return $out;
	}
	public function __construct(){
		$this->server = Server::getInstance();
		$this->hub = HubPlugin::get();
		$root = DP::registerPermission(new Perm("legionpe.chat", "Allow reading chat", Perm::DEFAULT_FALSE), $this->server->getPluginManager()->getPermission("legionpe"));
		foreach(static::defaultChannels() as $channel){
			DP::registerPermission(new Perm($channel.".read", "Allow reading chat from $channel", Perm::DEFAULT_FALSE), $root);
		}
		$pmgr = $this->server->getPluginManager();
		$pmgr->registerEvents($this, $this->hub);
	}
	/**
	 * @param PlayerChatEvent $evt
	 * @priority HIGHEST
	 */
	public function onChat(PlayerChatEvent $evt){
		$p = $evt->getPlayer();
		$evt->setCancelled(true);
		$msg = $evt->getMessage();
		$msg = $this->getPrefixes($p).$msg;
		$this->server->broadcast($msg, $this->getWriteChannel($p).";legionpe.chat.monitor");
	}
	public function onQuitCmd(CommandSender $issuer, array $args){
		if(!($issuer instanceof Player)){
			$issuer->sendMessage("Please run this command in-game.");
			return true;
		}
		$class = $this->getMgClass($issuer);
		$class::get()->onQuitMg($issuer, $args);
		$perm = $class::get()->getPermission();
		$this->hub->getPermAtt($issuer)->setPermission($perm, false);
		return true;
	}
	public function onStatCmd(CommandSender $issuer, array $args){
		if(!($issuer instanceof Player)){
			$issuer->sendMessage("Please run this command in-game.");
			return true;
		}
		$class = $this->getMgClass($issuer);
		if(!is_string($class)){
			$issuer->sendMessage("You are not in a minigame!");
		}
		else{
			$msg = $class::get()->getStats($issuer, $args);
			if(!is_string($msg)){
				$issuer->sendMessage("Stats is unavailable here.");
			}
			else{
				$issuer->sendMessage($msg);
			}
		}
		return true;
	}
	public function getMgClass(Player $player, $acceptNonMg = true, $simple = false){
		if($acceptNonMg and $this->hub->getSession($player) === HubPlugin::HUB or $this->hub->getSession($player) === HubPlugin::SHOP){
			if($simple){
				return ($this->hub->getSession($player) === HubPlugin::HUB) ? "":"shops.";
			}
			return ($this->hub->getSession($player) === HubPlugin::HUB) ? "\\pemapmodder_dep\\legionpe\\hub\\Hub":"\\pemapmodder_dep\\legionpe\\hub\\Shops";
		}
		switch($this->hub->getSession($player)){
			case HubPlugin::PVP:
				$out = "pvp\\Pvp";
				break;
			case HubPlugin::PK:
				$out = "pk\\Parkour";
				break;
			case HubPlugin::SPLEEF:
				$out = "spleef\\Main";
				break;
			case HubpLugin::CTF:
				$out = "ctf\\Main";
				break;
			default:
				return false;
		}
		return $simple ? (strstr($out, "\\", true)."."):"\\pemapmodder_dep\\legionpe\\mgs\\$out";
	}
	public function onQuit(PlayerQuitEvent $event){
		if(($s = $this->hub->sessions[$event->getPlayer()->getID()]) > HubPlugin::HUB and $s <= HubPlugin::ON)
			$this->server->dispatchCommand($event->getPlayer(), "quit");
	}
	protected function getPrefixes(Player $player){
		$prefix = "";
		foreach(HubPlugin::getPrefixOrder() as $pfxType=>$filter){
			if($pfxType === "team")
				$pf = "{".ucfirst(Team::get(HubPlugin::get()->getDb($player)->get("team"))["name"])."}";
			else $pf = ucfirst(HubPlugin::get()->getDb($player)->get("prefixes")[$pfxType]);
			if(!$this->isFiltered($filter, $player->getLevel()->getName()) and strlen(str_replace(" ", "", $pf)) > 0)
				$prefix .= "$pf|";
		}
		return $prefix;
	}
	protected function isFiltered($filter, $dirt){
		switch($filter){
			case "all":
				return false;
			case "pvp":
				return !in_array($dirt, array("world_pvp"));
			case "pk":
				return !in_array($dirt, array("world_parkour"));
			case "ctf":
				return !in_array($dirt, array("world_tmp_ctf", "world_base_ctf"));
			case "spleef":
				return stripos($dirt, "spleef") === false;
			default: // invalid filter?
				console("[WARNING] Invalid filter: \"$filter\"");
				return false;
		}
	}
	/**
	 * Serves as a final check that avoids the player from changing the world
	 * @param PlayerInteractEvent $evt
	 * @priority MONITOR
	 * @disclaimer I don't care. I WILL change things at MONITOR
	 * @ignoreCancelled true
	 */
	public function onInteractLP(PlayerInteractEvent $evt){
		$p = $evt->getPlayer();
		if(HubPlugin::get()->getRank($p) !== "staff")
			$evt->setCancelled(true);
	}
//	public function onMove(EntityMoveEvent $evt){
//		$p = $evt->getEntity();
//		if(!($p instanceof Player)){
//			return;
//		}
//		if(time() - ((int) @$this->teleports[$p->getID()]) <= 3){
//			return;
//		}
//		if(RL::enterPvpPor()->isInside($p)){
//			$this->joinMg($p, Pvp::get());
//		}
//		elseif(RL::enterPkPor()->isInside($p)){
//			$this->joinMg($p, Parkour::get());
//		}
//		elseif(RL::enterCtfPor()->isInside($p)){
//			$this->joinMg($p, CTF::get());
//		}
//		elseif(RL::enterSpleefPor()->isInside($p)){
//			$this->joinMg($p, Spleef::get());
//		}
//	}
	protected function joinMg(Player $p, MgMain $mg){
		$TID = $this->hub->getDb($p)->get("team");
		if(($reason = $mg->isJoinable($p, $TID)) === true){
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($p, "teleport"), $this->hub, $mg->getSpawn($p, $TID)), 40);
			$p->teleport($mg->getSpawn($p, $TID));
			$p->sendMessage("You are teleported to the");
			$p->sendMessage("  ".$mg->getName()." world! You might lag!");
			$this->teleports[$p->getID()] = time();
			$this->hub->sessions[$p->getID()] = $mg->getSessionId();
			if(!$this->hub->getDb($p)->get("mute")){
				$this->setWriteChannel($p, $mg->getDefaultChatChannel($p, $TID));
			}
			$this->hub->getPermAtt($p)->setPermission($mg->getPermission(), true);
			$mg->onJoinMg($p);
		}
		else{
			$p->sendMessage("{$mg->getName()} cannot be joined currently due to $reason!");
			$p->teleport(RL::spawn());
		}
	}
	public function setWriteChannel(Player $player, $channel = "legionpe.chat.general"){
		$this->channels[$player->getID()] = $channel;
	}
	public function getWriteChannel(Player $player){
		return $this->channels[$player->getID()];
	}
	public function addReadChannel(Player $player, $channel = "legionpe.chat.general", $value = true){
		$this->hub->getPermAtt($player)->setPermission($channel, $value);
	}
	public function removeReadChannel(Player $player, $channel){
		$this->hub->getPermAtt($player)->unsetPermission($channel);
	}
	public function getReadChannels(Player $player){
		$out = [];
		foreach($this->hub->getPermAtt($player)->getPermissions() as $perm => $bool){
			if(strpos($perm, "legionpe.chat.") === 0 and $bool === true){
				$out[] = $perm;
			}
		}
		return $out;
	}
	public function onPreCmd(PlayerCommandPreprocessEvent $event){
		$p = $event->getPlayer();
		if(substr($event->getMessage(), 0, 1) !== "/"){
			return;
		}
		$cmd = explode(" ", $event->getMessage());
		$command = substr(array_shift($cmd), 1);
		switch($command){
			case "me":
				$event->setCancelled(true);
				$this->server->broadcast("* {$p->getDisplayName()} ".implode(" ", $cmd), $this->getWriteChannel($p));
				break;
			case "spawn":
				$event->setCancelled(true);
				$event->getPlayer()->sendMessage("Reminder: use /quit next time!");
				$this->server->dispatchCommand($event->getPlayer(), "quit".substr($event->getMessage(), 1 + 5)); // "/" . "spawn": 1 + 5
				break;
		}
	}
	public function onCommand(Issuer $isr, Command $cmd, $lbl, array $args){
		$output = "";
		switch($cmd->getName()){
			case "chat":
				break;
			case "channel":
				break;
			case "mute":
				if(!isset($args[0])){
					return "Usage: /mute <player> [minutes = 30]";
				}
				$target = $this->server->getPlayer($name = array_shift($args));
				if(!($target instanceof Player)){
					return "Player not found!";
				}
				$length = 30;
				if(isset($args[0]) and is_numeric($args[0])){
					$length = floatval($length);
				}
				$ip = $target->getAddress();
				$this->mutes[$ip] = new CallbackPluginTask(array($this, "unmute"), $this->hub, $ip);
				$this->server->getScheduler()->scheduleDelayedTask($this->mutes[$ip], (int) ($length * 1200));
				$this->mute($ip);
				$this->server->broadcast("$ip has been muted for $length minutes by ".$isr->getName(), Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
				$this->server->broadcast($target->getDisplayName()." has been muted for $length minutes", $this->getWriteChannel($target));
				return true;
			case "unmute":
				if(!isset($args[0])){
					return "Usage: /unmute <player|IP>";
				}
				if(preg_replace("#[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}#", "", $name = $args[0]) === ""){
					$ip = $name;
				}
				else{
					$player = $this->server->getPlayer($name);
					if(!($player instanceof Player)){
						return "Player not found!";
					}
					$ip = $player->getAddress();
				}
				if(!isset($this->mutes[$ip])){
					return "$ip is not muted.";
				}
				$this->mutes[$ip]->cancel();
				$this->server->broadcast("$ip has been unmuted by ".$isr->getName(), Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
				return "$ip has been unmuted.";
		}
		return true;
	}
	public function parseChannel(Player $player, $chan){

	}
	public function addCoins(Player $player, $coins, $reason = "you-guess-why", $silent = false){
		$this->hub->getDb($player)->set("coins", $this->hub->getDb($player)->get("coins") + $coins);
		$this->hub->logp($player, "$coins coins received for $reason.");
		if(!$silent){
			$player->sendMessage("You have received $coins for $reason.");
		}
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
	/**
	 * @return static
	 */
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
}
