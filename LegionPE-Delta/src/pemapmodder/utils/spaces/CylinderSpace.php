<?php

namespace pemapmodder\utils\spaces;

use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;

class CylinderSpace extends Space{
	const X = 0;
	const Y = 1;
	const Z = 2;
	
	public $centre, $axis, $radius, $height;
	/**
	 * Constructs a new CylinderSpace object.
	 * @param int $axis
	 * @param Position $baseCentre
	 * @param float $radius
	 * @param int $height
	 */
	public function __construct($axis, Position $baseCentre, $radius, $height){
		$this->axis = (int) $axis % 3;
		$this->centre = $baseCentre;
		$this->radius = $radius;
		$this->height = $height;
	}
	/**
	 * @param bool $get
	 * @return Position[]|Block[]
	 */
	public function getBlockMap($get = false){
		$height = $this->height;
		$radius = $this->radius;
		$result = array();
		if($this->axis === self::Y){ // any more efficient ways?
			for($y = $this->centre->y; $y < $this->centre->y + $height; $y++){
				for($x = $this->centre->x - $this->radius; $x <= $this->centre->x + $radius; $x++){
					for($z = $this->centre->z - $this->radius; $z <= $this->centre->z + $radius; $z++){
						$pos = new Vector3($x, $y, $z);
						if($pos->distance(new Vector3($this->centre->x, $y, $this->centre->z)) <= $this->radius){
							if($get)
								$result[] = $this->centre->getLevel()->getBlock($pos);
							else $result[] = new Position($x, $y, $z, $this->centre->getLevel());
						}
					}
				}
			}
			return $result;
		}
		if($this->axis === self::X){
			for($x = $this->centre->x; $x < $this->centre->x + $height; $x++){
				for($y = $this->centre->y - $this->radius; $y <= $this->centre->y + $radius; $y++){
					for($z = $this->centre->z - $this->radius; $z <= $this->centre->z + $radius; $z++){
						$pos = new Vector3($x, $y, $z);
						if($pos->distance(new Vector3($x, $this->centre->y, $this->centre->z)) <= $this->radius){
							if($get)
								$result[] = $this->centre->getLevel()->getBlock($pos);
							else $result[] = new Position($x, $y, $z, $this->centre->getLevel());
						}
					}
				}
			}
			return $result;
		}
		if($this->axis === self::Z){
			for($z = $this->centre->z; $z < $this->centre->z + $height; $z++){
				for($y = $this->centre->y - $this->radius; $y <= $this->centre->y + $radius; $y++){
					for($x = $this->centre->x - $this->radius; $x <= $this->centre->x + $radius; $x++){
						$pos = new Vector3($x, $y, $z);
						if($pos->distance(new Vector3($this->centre->x, $this->centre->y, $z)) <= $this->radius){
							if($get)
								$result[] = $this->centre->getLevel()->getBlock($pos);
							else $result[] = new Position($x, $y, $z, $this->centre->getLevel());
						}
					}
				}
			}
			return $result;
		}
		return null;
	}
	public function isInside(Position $pos){
		if($this->axis === self::X){
			$v2 = new Vector2($pos->y, $pos->z);
			return $pos->x === $this->centre->x and $v2->distance($this->centre->y, $this->centre->z) <= $this->radius;
		}
		if($this->axis === self::Y){
			$v2 = new Vector2($pos->x, $pos->z);
			return $pos->y === $this->centre->y and $v2->distance($this->centre->x, $this->centre->z) <= $this->radius;
		}
		if($this->axis === self::Z){
			$v2 = new Vector2($pos->y, $pos->x);
			return $pos->z === $this->centre->z and $v2->distance($this->centre->y, $this->centre->x) <= $this->radius;
		}
		return false;
	}
	public function setBlocks(Block $block){
		$cnt = 0;
		foreach($this->getBlockMap(true) as $pos){
			if($pos->getID() !== $block->getID() or $pos->getDamage() !== $block->getDamage()){
				$this->centre->getLevel()->setBlock($pos, $block, false, false, true);
				$cnt++;
			}
		}
		return $cnt;
	}
	public function replaceBlocks(Block $old, Block $new, $detectMeta = true){
		$cnt = 0;
		foreach($this->getBlockMap(true) as $pos){
			if($pos->getID() === $old->getID() and ($pos->getDamage() === $old->getDamage() or $detectMeta === false)){
				$this->centre->getLevel()->setBlock($pos, $new, false, false, true);
				$cnt++;
			}
		}
		return $cnt;
	}
}
