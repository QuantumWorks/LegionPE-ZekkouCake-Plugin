<?php

namespace pemapmodder\legionpe\mgs;

use pemapmodder\legionpe\hub\HubPlugin;
use pocketmine\block\IronBars;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\item\DiamondPickaxe;
use pocketmine\item\GoldShovel;
use pocketmine\item\IronSword;

class MinigameMenu extends BaseInventory{
	private $plugin;
	public function __construct(HubPlugin $plugin){
		$this->plugin = $plugin;
		parent::__construct($plugin, InventoryType::get(InventoryType::PLAYER),
			[new IronSword(), new IronBars(), new GoldShovel(), new DiamondPickaxe()]);
	}
}
