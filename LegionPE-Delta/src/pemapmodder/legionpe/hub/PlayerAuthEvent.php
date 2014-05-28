<?php

namespace pemapmodder\legionpe\hub;

use pocketmine\Player;
use pocketmine\event\player\PlayerEvent;

class PlayerAuthEvent extends PlayerEvent{
	public static $handlerList = null;
	public function __construct(Player $player){
		$this->player = $player;
	}
}
