<?php

namespace pemapmodder\legionpe\mgs;

use pemapmodder\legionpe\hub\Hub;
use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\ctf\Main as CTF;
use pemapmodder\legionpe\mgs\infected\Main as Infected;
use pemapmodder\legionpe\mgs\pk\Parkour;
use pemapmodder\legionpe\mgs\pvp\Pvp;
use pemapmodder\legionpe\mgs\spleef\Main as Spleef;
use pocketmine\block\IronBars;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\item\Block;
use pocketmine\item\DiamondPickaxe;
use pocketmine\item\GoldShovel;
use pocketmine\item\IronSword;
use pocketmine\item\WoodenPickaxe;
use pocketmine\item\WoodenSword;
use pocketmine\Player;

class MinigameMenu extends BaseInventory implements Listener, CommandExecutor{
	protected $plugin;
	protected $mgs;
	public function __construct(HubPlugin $plugin){
		$this->plugin = $plugin;
		parent::__construct($plugin, InventoryType::get(InventoryType::PLAYER),
			[new WoodenPickaxe, new IronSword, new Block(new IronBars), new GoldShovel, new DiamondPickaxe, new WoodenSword]);
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$this->mgs = [
			Hub::get(),
			Pvp::get(),
			Parkour::get(),
			Spleef::get(),
			CTF::get(),
			Infected::get(),
		];
	}
	public function onChangeItem(PlayerItemHeldEvent $event){
		foreach($this->getViewers() as $viewer){
			if(is_object($viewer) and spl_object_hash($viewer) === spl_object_hash($viewer)){
				if(!isset($this->mgs[$event->getInventorySlot()])){
					$event->setCancelled();
					return;
				}
				Hub::get()->joinMg($event->getPlayer(), $this->mgs[$event->getInventorySlot()]);
			}
		}
	}
	public function onCommand(CommandSender $issuer, Command $cmd, $lbl, array $args){
		if($cmd->getName() === "mgs" and ($issuer instanceof Player)){
			if(isset($this->viewers[spl_object_hash($issuer)])){
				unset($this->viewers[spl_object_hash($issuer)]);
				$issuer->getInventory()->sendContents($issuer);
				return true;
			}
			$this->viewers[spl_object_hash($issuer)] = $issuer;
			$this->sendContents($issuer);
		}
		return true;
	}
	public function onQuit(PlayerQuitEvent $event){
		if(isset($this->viewers[$hash = spl_object_hash($event->getPlayer())])){
			unset($this->viewers[$hash]);
		}
	}
}
