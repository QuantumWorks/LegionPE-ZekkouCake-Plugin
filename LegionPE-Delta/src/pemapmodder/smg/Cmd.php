<?php

namespace pemapmodder\smg;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;

class Cmd extends Command implements PluginIdentifiableCommand{
	public function __construct($name, $desc, $use, Plugin $p, CommandExecutor $exe, $perm, $aliases = []){
		parent::__construct($name, $desc, $use, $aliases);
		$this->plugin = $p;
		$this->exe = $exe;
		$this->setPermission($perm);
	}
	public function execute(CommandSender $issuer, $lbl, array $args){
		$issuer->sendMessage($this->exe->onCommand($issuer, $this, $lbl, $args));
		return true;
	}
	public function getPlugin(){
		return $this->plugin;
	}
}
