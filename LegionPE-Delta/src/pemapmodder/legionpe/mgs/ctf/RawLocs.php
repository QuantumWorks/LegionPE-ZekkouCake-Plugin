<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\geog\RawLocs as ParentClass;

use pocketmine\Server;
use pocketmine\level\Position as Pos;

abstract class Rawlocs extends ParentClass{
	public final static function baseName(){
		return "world_base_ctf";
	}
	public final static function basePath(){
		return Server::getInstance()->getDatapath()."worlds/".self::baseName();
	}
	public final static function worldName(){
		return "world_temp_ctf";
	}
	public final static function worldPath(){
		return Server::getInstance()->getDatapath()."worlds/".self::worldName();
	}
	public final static function world(){
		return Server::getInstance()->getLevel(self::worldName());
	}
	public final static function pSpawn($tid){
		switch($tid & 0b11){
			case 1:
				return new Pos(self::world());
			case 2:
				return new Pos(self::world());
			case 3:
				return new Pos(self::world());
			case 4 & 0b11:
				return new Pos(self::world());
		}
	}
}
