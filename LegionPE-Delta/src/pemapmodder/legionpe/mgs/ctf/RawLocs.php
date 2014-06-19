<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\geog\RawLocs as ParentClass;
use pocketmine\block\Block;
use pocketmine\Server;
use pocketmine\level\Position as Pos;

class Rawlocs extends ParentClass{
	const FLAG_0 = 0b00000100;
	const FLAG_1 = 0b00000101;
	const FLAG_2 = 0b00000110;
	const FLAG_3 = 0b00000111;
	const FLAG_RETURN = 0b00001000;

	public final static function baseName(){
		return "world_base_ctf";
	}
	public final static function basePath(){
		return Server::getInstance()->getDataPath()."worlds/".self::baseName();
	}
	public final static function worldName(){
		return "world_temp_ctf";
	}
	public final static function worldPath(){
		return Server::getInstance()->getDataPath()."worlds/".self::worldName();
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
		return 0; // just to silent PHPStorm
	}
	public final static function identifyBlock(Block $block){
		$loc = $block->getFloorX().",".$block->getFloorY().",".$block->getFloorZ();
		switch($loc){
			case "128,127,128": // a random thing
				return false;
		}
		return false;
	}
	public final static function isBlockBreakable(){
		return false;
	}
	public function init(){

	}
}
