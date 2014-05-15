<?php

namespace pemapmodder\smg;

use poxketmine\Server;
use pocketmine\event\EventPriority as EP;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase as ParentClass;

class Main extends ParentClass implements Listener{
	const PERMANENT = 0;
	public function onEnable(){
		$this->initEvts();
	}
	public function onCommand(Issuer $isr, Command $cmd, $lbl, array $args){
		
	}
	public function banIP($hours = self::PERMANENT, $reason){
		$this->getServer();
	}
}
