<?php

namespace legionpe\minigames\kitpvp;

use legionpe\Main;
use legionpe\Minigame;

class KitPvp extends Minigame{
	public function __construct(Main $main){

	}
	public function getName(){
		return "KitPvP";
	}
	public function getSpawn(){
		// TODO
	}
	public function ownLevel($world){
		return in_array($world, ["world_pvp"]);
	}
}
