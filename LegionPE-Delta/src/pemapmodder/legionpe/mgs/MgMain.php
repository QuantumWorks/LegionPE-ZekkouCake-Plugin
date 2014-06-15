<?php

namespace pemapmodder\legionpe\mgs;

use pocketmine\Player;

abstract class MgMain{
	public abstract  function onJoinMg(Player $player);
	public abstract function onQuitMg(Player $player);
	public abstract function getName();
	public abstract function getSessionId();
	/**
	 * @param Player $player
	 * @param $TID
	 * @return mixed
	 */
	public abstract function getSpawn(Player $player, $TID);
	/**
	 * @param Player $player
	 * @param $TID
	 * @return mixed
	 */
	public abstract function getDefaultChatChannel(Player $player, $TID);
	/**
	 * @param Player $player
	 * @param $t
	 * @return mixed
	 */
	public abstract function isJoinable(Player $player, $t);
	/**
	 * @param Player $player
	 * @param array $args
	 * @return mixed
	 */
	public abstract function getStats(Player $player, array $args = []);
//	public abstract static function init();
//	/**
//	 * @return self
//	 */
//	public abstract static function get();
}
