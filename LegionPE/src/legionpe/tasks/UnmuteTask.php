<?php

namespace legionpe\tasks;

use legionpe\Main;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class UnmuteTask extends PluginTask{
	private $player;
	public function __construct(Main $main, Player $player){
		parent::__construct($main);
		$this->player = $player->getID();
	}
	public function onRun($ticks){
		/** @var Main $main */
		$main = $this->getOwner();
		$main->getChatManager()->mutes[$this->player] = false;
	}
}
