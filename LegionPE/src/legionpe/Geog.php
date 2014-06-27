<?php

namespace legionpe;

use pocketmine\level\Position;
use pocketmine\Server;

abstract class Geog{
	public static function spawn(){
		return new Position(128, 4, 128, self::spawnWorld());
	}
	public static function teamSign($team){
		return new Position(128, 4, 128 + $team, self::spawnWorld());
	}
	public static function spawnWorld(){
		return Server::getInstance()->getLevelByName("world");
	}
}
