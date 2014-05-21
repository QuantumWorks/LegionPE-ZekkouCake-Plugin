<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\legionpe\hub\Team;
use pemapmodder\legionpe\mgs\MgMain;
use pemapmodder\legionpe\mgs\pvp\Pvp;
use pemapmodder\legionpe\mgs\pk\Parkour as Parkour;
use pemapmodder\legionpe\mgs\spleef\Main as Spleef;
use pempamodder\legionpe\mgs\ctf\Main as CTF;

use pemapmodder\utils\CallbackEventExe;
use pemapmodder\utils\CallbackPluginTask;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor as CmdExe;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
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
	protected $writePA = array();
	protected $mutePA = array();
	protected $pchannel = array();
	protected $channels = array();
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
			"legionpe.chat.spleef.TID",
			"legionpe.chat.spleef.SID",
			"legionpe.chat.spleef.SID.TID",
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
		$root = DP::registerPermission(new Perm("legionpe.chat", "Allow reading chat"), $this->server->getPluginManager()->getPermission("legionpe"));
		foreach(static::defaultChannels() as $channel){
			DP::registerPermission(new Perm($channel.".read", "Allow reading chat from $channel", Perm::DEFAULT_FALSE), $root);
		}
		$pmgr = $this->server->getPluginManager();
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::LOW, new CallbackEventExe(array($this, "onInteractLP")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\entity\\EntityMoveEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onMove")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerChatEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onChat")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerQuitEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onQuit")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerJoinEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onJoin")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerCommandPreprocessEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onPreCmd")), HubPlugin::get());
	}
	public function onChat(Event $evt){
		$pfxs = HubPlugin::get()->getDb($p = $evt->getPlayer())->get("prefixes");
		$pfxs["team"] = Team::get(HubPlugin::get()->getDb($p)->get("team"))["name"];
		$rec = array();
		foreach($evt->getRecipients() as $r){
			$chan = $this->pchannels[$p->CID];
			if($r->hasPermission($chan.".read")){
				$rec[] = $r;
				break;
			}
		}
		$evt->setRecipients($rec);
		$format = $this->getPrefixes($p)."%s: %s";
		$evt->setFormat($format);
	}
	public function onQuitCmd($issuer, array $args){
		if(!($issuer instanceof Player)){
			$issuer->sendMessage("Please run this command in-game.");
			return true;
		}
		$class = $this->getMgClass($issuer);
		$class
		return true;
	}
	public function onStatCmd($issuer, array $args){
		if(!($issuer instanceof Player)){
			$issuer->sendMessage("Please run this command in-game.");
			return true;
		}
		$class = $this->getMgClass($issuer);
		if(!is_string($class)){
			$issuer->sendMessage("You are not in a minigame!");
		}
		else{
			$msg = $class::get()->getStats();
			if(!is_string($msg)){
				$issuer->sendMessage("Stats unavailable.");
			}
			else{
				$issuer->sendMessage($msg);
			}
		}
	}
	public function getMgClass(Player $player){
		switch($this->hub->getSession($issuer)){
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
		return "pemapmodder\\legionpe\\mgs\\$out";
	}
	public function onQuit(Event $event){
		if(($s = $this->hub->sessions[$event->getPlayer()->CID]) > HubPlugin::HUB and $s <= HubPlugin::ON)
			$this->server->dispatchCommand($event->getPlayer(), "quit");
	}
	protected function getPrefixes(Player $player){
		$prefix = "";
		foreach(HubPlugin::getPrefixOrder() as $pfxType=>$filter){
			if($pfxType === "team")
				$pf = "{".ucfirst(Team::get(HubPlugin::get()->getDb($player)->get("team"))["name"])."}";
			else $pf = ucfirst(HubPlugin::get()->getDb($player)->get("prefixes")[$pfxType]);
			if(!$this->isFiltered($filter, $p->level->getName()) and strlen(str_replace(" ", "", $pf)) > 0)
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
	public function onInteractLP(Event $evt){
		$p = $evt->getPlayer();
		if(HubPlugin::getRank($p) !== "staff")
			$evt->setCancelled(true);
	}
	public function onMove(Event $evt){
		$p = $evt->getEntity();
		if(!($p instanceof Player))
			return;
		if(time() - ((int)@$this->teleports[$p->CID]) <= 3)
			return;
		if(RL::enterPvpPor()->isInside($p)){
			$this->joinMg($p, Pvp::get());
		}
		elseif(RL::enterPkPor()->isInside($p)){
			$this->joinMg($p, Parkour::get());
		}
	}
	protected function joinMg(Player $p, MgMain $mg){
		$TID = $this->hub->getDb($p)->get("team");
		if(($reason = $mg->isJoinable($p, $TID)) === true){
			$this->server->getScheduler()->scheduleDelayedTask(
					new CallbackPluginTask(array($p, "teleport"), $this->hub, $mg->getSpawn($p, $TID)), 40);
			$p->teleport($mg->getSpawn($p, $TID));
			$p->sendMessage("You are teleported to the");
			$p->sendMessage("  ".$mg->getName()." world! You might lag!");
			$this->teleports[$p->CID] = time();
			$this->hub->sessions[$p->CID] = $mg->getSessionId();
			if(!$this->hub->getDb($p)->get("mute"))
				$this->setChannel($p, $mg->getDefaultChatChannel($p, $TID));
			$mg->onJoinMg($p);
		}
		else{
			$p->sendMessage("{$mg->getName()} cannot be joined currently due to $reason!");
			$p->teleport(RL::spawn());
		}
	}
	public function setChannel(Player $player, $channel = "legionpe.chat.general", $writeOnly = false, $reserveOld = false){
		$oldChannel = $this->pchannels[$player->CID];
		$this->pchannels[$player->CID] = $channel;
		if(!$writeOnly){
			$this->readPA[$player->CID][$channel] = $player->addAttachment($channel.".read", true);
		}
		if(!$reserveOld){
			$player->removeAttachment($this->readPA[$player->CID][$oldChannel]);
			unset($this->readPA[$player->CID][$oldChannel]);
		}
	}
	public function getChannel(Player $player){
		return $this->pchannels[$player->CID];
	}
	public function onJoin(Event $event){
		$event->getPlayer()->addAttachment($this->hub, "legionpe.chat.mandatory", true);
	}
	public function onPreCmd(Event $event){
		$p = $event->getPlayer();
		if(substr($event->getMessage(), 0, 1) !== "/"){
			return;
		}
		$cmd = explode(" ", $event->getMessage());
		$command = substr(array_shift($cmd), 1);
		switch($command){
			case "me":
				$event->setCancelled(true);
				foreach(Player::getAll() as $player){
					if($this->getChannel($player) === $this->getChannel($p) or $this->getChannel($p) === "legionpe.chat.mandatory")
						$player->sendMessage("* {$this->getPrefixes($player)}{$player->getDisplayName()} ".implode(" ", $cmd));
				}
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
		if(!($isr instanceof Player))
			return "Please run this command in-game.";
		switch($cmd->getName()){
			case "mute":
			case "unmute":
				array_unshift($args, $cmd->getName());
			case "chat":
				if(!($isr instanceof Player))
					return "Please run this command in-game.";
				switch($subcmd = array_shift($args)){
					case "mute":
						$this->mutePA[$isr->CID] = array();
						foreach(self::defaultChannels() as $channel){
							$this->mutePA[$isr->CID][] = $isr->addAttachment($this->hub, "$channel.read", false);
						}
						break;
					case "unmute":
						while(count($this->mutePA[$isr->CID])){
							$att = array_shift($this->mutePA[$isr->CID]);
							$isr->removeAttachment($att);
						}
						break;
					case "ch":
						if(!$isr->hasPermission("legionpe.cmd.chat.ch"))
							return "You don't have permission to use /chat ch";
						if(isset($args[0])){
							$ch = array_shift($args);
							if($isr->hasPermission($ch = $this->parseChannel($isr, $ch))){
								return "Your chat channel has been set to \"$ch\"";
							}
							return "You don't have permission to create/join this chat channel";
						}
				}
			case "help":
				$output = "Showing help of /chat, /mute and /unmute:\n";
			default:
				$output .= "/unmute: Equal to /chat unmute";
				$output .= "/mute: Equal to /chat mute";
				$output .= "/chat mute: Equal to \"/chat ch m\" or \"/chat ch mute\"";
				$output .= "/chat ch <channel> Join a chat channel";
				return $output;
		}
	}
	public function parseChannel(Player $player, $ch){
		$mg = "";
		$s = $this->hub->getSession($player);
		if(){}
		switch($ch){
			case 0:break; // TODO
		}
	}
	public function addCoins(Player $player, $coins, $reason = "you-guess-why", $silent = false){
		$this->hub->getDb($player)->set("coins", $this->hub->getDb($player)->get("coins") + $coins);
		$this->hub->logp($player, "$coins coins received for $reason.");
		if(!$silent){
			$player->sendMessage("You have received $coins for $reason.");
		}
	}
	public static $inst = false;
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
}
