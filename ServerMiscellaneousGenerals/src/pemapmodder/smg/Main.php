<?php

namespace pemapmodder\smg;

use pemapmodder\utils\CallbackEventExe as Exe;
use pemapmodder\utils\CorrPluginCmd as Cmd;

use pocketmine\event\EventPriority as EP;
use pocketmine\event\Listener;
use pocketmine\permission\DefaultPermissions as DP;
use pocketmine\permission\Permission as Perm;
use pocketmine\plugin\PluginBase as ParentClass;

class Main extends ParentClass implements Listener{
	public function onEnable(){
		$this->initPerms();
		$this->initCmds();
		$this->initEvts();
	}
	protected function initPerms(){
		$root = DP::registerPermission(new Perm("smg", "Allow using all SMG utilities"));
		$mod = DP::registerPermission(new Perm("smg.mod", "Allow acceessing moderator functions in SMG", Perm::DEFAULT_FALSE), $root);
		$penalty = DP::registerPermission(new Perm("smg.mod.penalty", "Allow using moderator identity to issue penalties"), $mod);
		$admin = DP::registerPermission(new Perm("smg.admin", "Allow accessing admin functions in SMG", Perm::DEFAULT_FALSE), $root);
	}
	protected function initEvts(){
		$this->getServer()->getPluginManager()->registerEvent("pocketmine\\event\\player\\PlayerJoinEvent", );
	}
	protected function initCmds(){
		$cmd = new Cmd("penalty", $this);
		$cmd->setPermission("smg.mod.penalty");
		$cmd->setDescription("Issue a penalty on a player as a mod");
		$this->getServer()->getCommandMap()->register("smg", $cmd);
	}
	public function 
}
