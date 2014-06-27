<?php

namespace legionpe\minigames\ctf;

use legionpe\Main;
use legionpe\Minigame;

class CTF extends Minigame{
	private $main;
	public function __construct(Main $main){
		$this->main = $main;
	}
	public function getName(){
		return "ctf";
	}
	public function getSpawn(){
		// TODO
	}
}
