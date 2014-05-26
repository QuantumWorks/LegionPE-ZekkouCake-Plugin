<?php

namespace pemapmodder\smg;

use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase as ParentClass;
use pocketmine\utils\Config;

class Main extends ParentClass implements Listener{
	const REGPEN	= 1;
	const REGBAN	= 3;
	const PERMBAN	= 15;

	const SPAM			= 0b000000000001; // 1
	const HARRASS		= 0b000000000010; // 2
	const SWEAR			= 0b000000000100; // 4
	const STAFF_IMPOSE	= 0b000000001000; // 8
	const CLIMB_MOD		= 0b000000010000; // 16
	const FLY_MOD		= 0b000000100000; // 32
	const JUMP_MOD		= 0b000001000000; // 64
	const SPRINT_MOD	= 0b000010000000; // 128
	const GLITCH_USE	= 0b000100000000; // 256
	const IMPROPER_CHAT	= 0b001000000000; // 512
	const MOD_USE		= 0b000011110000; // 235

	const ADMIN = "admin";
	const MOD_GLOB = "global moderator";
	const MOD_SEC = "sectional moderator";
	const NORM = "player";
	/**
	 * @var Penalty[] indexed according to issuer CID
	 */
	public $penalties = [];
	/**
	 * @var BanList
	 */
	public $list;
	/**
	 * @var ActionLogger
	 */
	public $actionLogger;
	/**
	 * @var Config
	 */
	public $ranks;
	/**
	 * @var ReportList
	 */
	public $reportList;
	public function onEnable(){
		$this->actionLogger = new ActionLogger($this);
		$this->list = new BanList($this->getDataFolder()."ban-list.json");
		$this->ranks = new Config($this->getDataFolder()."config.yml", Config::YAML, [
			"admin" => ["lambo", "spyduck", "pemapmodder"],
			"mods" => [
				"global" => [
					"player1",
					"player2",
				],
				"sectional" => [
					"playername" => ["world_one", "world_two"],
	            ]
			]
		]);
		$this->getServer()->registerEvents($this, $this);
		$this->reportList = new ReportList;
	}
	public function getRank(Player $player){
		$name = strtolower($player->getName());
		foreach($this->ranks->get("admin") as $member){
			if(strtolower($member) === $name){
				return self::ADMIN;
			}
		}
		foreach($this->ranks->get("mods")["global"] as $member){
			if(strtolower($member) === $name){
				return self::MOD_GLOB;
			}
		}
		foreach($this->ranks->get("mods")["sectional"] as $mod => $worlds){
			if($mod === $name){
				return self::MOD_SEC;
			}
		}
		return self::NORM;
	}
	public function hasPermission(Player $player, $worldName){
		$rank = $this->getRank($player);
		if($rank === self::ADMIN or $rank === self::MOD_GLOB){
			return true;
		}
		if($rank === self::NORM){
			return false;
		}
		$worlds = $this->ranks->get("mods")["sectional"][strtolower($player->getName())];
		return in_array($worldName, $worlds);
	}
	/**
	 * @param PlayerPreLoginEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPreLogin(PlayerPreLoginEvent $event){
		$time = 0;
		if($this->getBanList()->isBanned($event->getPlayer()->getAddress(), $time)){
			$event->setCancelled(true);
			$time /= (60 * 60);
			if($time <= 36){
				$time = round($time, 1);
				$time = "$time hour(s)";
			}
			else{
				$time /= 24;
				$time = round($time, 1);
				$time = "$time day(s)";
			}
			$event->setKickMessage("Banned by ServerMiscellaneousGenerals: $time left until ban lift");
		}
	}
	/**
	 * @param Issuer $isr
	 * @param Command $cmd
	 * @param string $lbl
	 * @param array $args
	 * @return bool
	 */
	public function onCommand(Issuer $isr, Command $cmd, $lbl, array $args){
		if(!($isr instanceof Player)){
			$isr->sendMessage("Please run this command in-game.");
			return true;
		}
		switch($cmd){
			case "report":
				if(!isset($args[2])){
					return false;
				}
				$name = array_shift($args);
				$player = $this->getServer()->getPlayer($name);
				if(!($player instanceof Player)){
					$isr->sendMessage("Player $name not found, aborting report.");
					return true;
				}
				if(!in_array(strtolower($args[0]), array("chat", "mod", "mods", "move"))){
					$isr->sendMessage("Unclassified report type $args[0]. Aborting report.");
					return true;
				}
				$type = strtolower($args[0]) === "chat";
				$details = implode(" ", $args);
				$this->reportList->add($report = new Report($player, $type, $details, $isr));
				$isr->sendMessage("You have successfully submitted report RID {$report->getID()} on player {$player->getName()} for ".($type ? "chat misbehavior":"using movement-related mods"));
				break;
			case "penalty":
				$this->penalties = $penalty = Penalty::add($isr, null, $this->evalStrFlags(array_shift($args)), array_shift($args), implode(" ", $args));
				break;
			case "report-view":
				$report = $this->getReportList()->read($isr);
				$isr->sendMessage("$report");
				break;
			case "report-view-log":
				$page = isset($args[0]) ? array_shift($args):1;
				return $this->getReportList()->read($isr)->getLog($page);
			case "report-mark-read":
				$this->getReportList()->markRead($isr);
				break;
			case "report-mark-resolved":
				$this->getReportList()->markResolved($isr);
				break;
			case "report-warn":
				$this->getReportList()->markWarned($isr, $this->evalStrFlags(array_shift($args)));
		}
	}
	/**
	 * @param string
	 * @return int
	 */
	public function evalStrFlags($flags, &$unknown = ""){
		$split = preg_split("#[\\|,;#", $flags);
		$flags = 0;
		foreach($split as $flag){
			switch($flag){
				case "spam":
				case "spamming":
					$flags |= self::SPAM;
					break;
				case "harass":
				case "harassing":
					$flags |= self::HARRASS;
					break;
				case "swear":
				case "swearing";
					$flags |= self::SWEAR;
					break;
				case "staff-impose":
				case "staff-imposement":
					$flags |= self::STAFF_IMPOSE;
					break;
				case "climb":
				case "climbing":
					$flags |= self::CLIMB_MOD;
					break;
				case "fly":
				case "flying":
					$flags |= self::CLIMB_MOD;
					break;
				case "jump":
				case "superjump":
					$flags |= self::JUMP_MOD;
					break;
				case "sprint":
					$flags |= self::SPRINT_MOD;
					break;
				case "glitch":
				case "glitching":
				case "bug":
					$flags |= self::GLITCH_USE;
					break;
				default:
					$unknown .= $flag.", ";
			}
		}
		$unknown = substr($unknown, 0, -2);
		return $flags;
	}
	/**
	 * @param int $flags
	 * @return [string[] $reasons, int $warningPoints]
	 */
	public function evalFlags($flags){
		$out = [];
		// reasons
		if($flags & self::SPAM){
			$out[] = "spam";
		}
		if($flags & self::HARRASS){
			$out[] = "harassing other players";
		}
		if($flags & self::SWEAR){
			$out[] = "swearing";
		}
		if($flags & self::STAFF_IMPOSE){
			$out[] = "staff imposement";
		}
		if($flags & self::CLIMB_MOD){
			$out[] = "climbing mod usage";
		}
		if($flags & self::FLY_MOD){
			$out[] = "flying mod usage";
		}
		if($flags & self::JUMP_MOD){
			$out[] = "super jump mod usage";
		}
		if($flags & self::SPRINT_MOD){
			$out[] = "using walk faster/sprinting mod";
		}
		if($flags & self::GLITCH_USE){
			$out[] = "improper usage of glitches or bugs";
		}
		if($flags & self::IMPROPER_CHAT){
			$out[] = "improper chat behaviours";
		}
		// actions
		$actions = 0;
		if($flags & self::MOD_USE){
			$actions += self::REGBAN;
		}
		if($flags & self::SPAM){
			$actions += self::REGBAN;
		}
		if($flags & self::HARRASS){
			$action += self::REGPEN;
		}
		if($flags & self::SWEAR){
			$actions += self::REGPEN;
		}
		if($flags & self::STAFF_IMPOSE){
			$actions += self::REGBAN;
		}
		return [$out, $actions];
	}
	/**
	 * @return ActionLogger
	 */
	public function getActionLogger(){
		return $this->actionLogger;
	}
	/**
	 * @return BanList
	 */
	public function getBanList(){
		return $this->list;
	}
	/**
	 * @return ReportList
	 */
	public function getReportList(){
		return $this->reportList;
	}
	/**
	 * @return null|Main
	 */
	public static function get(){
		return Server::getInstance()->getPluginManager()->getPlugin("ServerMiscellaneousGenerals");
	}
}
