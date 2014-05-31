<?php

namespace pemapmodder\legionpe\mgs;

use pocketmine\Player;

abstract class MgMain{
	public abstract  function onJoinMg(Player $player);
	public abstract function onQuitMg(Player $player);
	public abstract function getName();
	public abstract function getSessionId();
	/**
	 * @return pocketmine\level\Position
	 */
	public abstract function getSpawn(Player $player, $TID);
	/**
	 * @return string
	 */
	public abstract function getDefaultChatChannel(Player $player, $TID);
	/**
	 * @return bool
	 */
	public abstract function isJoinable();
	/**
	 * @return string|bool|null string stat message, boolean false or null
	 */
	public abstract function getStats(Player $player);
	public abstract static function init();
	/**
	 * @return self
	 */
	public abstract static function get();
}
