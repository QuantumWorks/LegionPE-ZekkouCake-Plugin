<?php

namespace pemapmodder\legionpe\geog;

use pocketmine\level\Position as PmPos;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class Position extends PmPos{
	/**
	 * @param int|PmPos $x
	 * @param int $y
	 * @param int $z
	 * @param Level $level
	 */
	public function __construct($x, $y=0, $z=0, Level $level=null){
		if($x instanceof PmPos)
			parent::__construct($x->x, $x->y, $x->z, $x->getLevel());
		else parent::__construct($x, $y, $z, $level);
	}
	public function equals(Vector3 $other){
		$result = $other->x === $this->x and $other->y === $this->y and $other->z === $this->z;
		if($other instanceof PmPos)
			$result = ($result and $other->getLevel()->getName() === $this->getLevel()->getName());
		return $result;
	}
}
