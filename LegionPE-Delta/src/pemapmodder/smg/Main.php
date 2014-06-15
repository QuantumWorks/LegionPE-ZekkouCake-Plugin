<?php

namespace pemapmodder\smg;

use pemapmodder\legionpe\hub\HubPlugin;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;

echo "SMG is loaded".PHP_EOL;

class SMG implements CommandExecutor, Listener{
	private $main;
	/** @var BanList */
	private $banList;
	/** @var PenaltyList */
	private $penaltyList;
	/** @var ReportList */
	private $reportList;
	public function __construct(HubPlugin $main){
		$this->main = $main;
		$this->server = $main->getServer();
		$this->logger = $main->getLogger();
		$this->banList = new BanList($this->server->getDataPath()."Hub/BanList.json");
		$this->penaltyList = new PenaltyList($this->server->getDataPath()."Hub/PenaltyList.json");
		$this->reportList = new ReportList($this->server->getDataPath()."Hub/ReportList.json");
		$this->registerCommands();
		$this->server->getPluginManager()->registerEvents($this, $main);
	}
	public function registerCommands(){
		$this->server->getCommandMap()->registerAll("smg", [
			new Cmd("report", "Submit a report", "/report <player> <description>", $this->main, $this, ["rep"]),
			new Cmd("view-report", "View the next unresolved report or the specified report with [id]", "/view-report [id]", $this->main, $this, ["vr", "viewrep"]),
			new Cmd("penalty", "Issue a penalty", "/penalty <player> <reason ID> [extra message]", $this->main, $this, ["pen"]),
			new Cmd("review-penalty", "Review a penalty with given ID", "/review-penalty <id>", $this->main, $this, ["rp"]),
		]);
	}
	public function onCommand(CommandSender $issuer, Command $cmd, $lbl, array $args){
		switch($cmd){
			case "report":
				break;
			case "view-report":
				break;
			case "penalty":
				break;
			case "review-penalty":
				break;
		}
		return "Command not handled!";
	}
	public function onPreLogin(PlayerPreLoginEvent $event){
		if(!$this->banList->canLogin(strtolower($event->getPlayer()->getName()))){
			$event->setCancelled(true);
			$event->setKickMessage("Banned (too many warning points)");
		}
	}
	public function finalize(){
		$this->banList->finalize();
		$this->reportList->finalize();
		$this->penaltyList->finalize();
	}
}
