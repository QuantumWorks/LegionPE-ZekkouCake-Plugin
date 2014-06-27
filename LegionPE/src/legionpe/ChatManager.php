<?php

namespace legionpe;

use legionpe\tasks\UnmuteTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;

class ChatManager implements Listener{
	private $main;
	public $writeChannels = [];
	public $mutes = [];
	private $unmutes = [];
	public function __construct(Main $main){
		$this->main = $main;
	}
	/**
	 * @param PlayerChatEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onChat(PlayerChatEvent $event){
		$event->setCancelled(true);
		$message = $event->getPlayer()->getDisplayName()."> ".$event->getMessage();
		$channels = $this->getWriteChannel($event->getPlayer());
		$this->main->getServer()->broadcast($message, $channels);
	}
	public function onMeCmd(Player $player, $message){
		$message = "* ".$player->getDisplayName()." ".$message;
		$channels = $this->getWriteChannel($player);
		$this->main->getServer()->broadcast($message, $channels);
	}
	public function getWriteChannel(Player $player){
		return $this->writeChannels[$player->getID()];
	}
	public function mute(Player $player, $seconds){
		$this->setMuted($player, true);
		$id = $this->main->getServer()->getScheduler()->scheduleDelayedTask(new UnmuteTask($this->main, $player), $seconds * 20)->getTaskId();
		$this->unmutes[$player->getID()] = $id;
	}
	public function unmute(Player $player){
		$this->setMuted($player, false);
		if(isset($this->unmutes[$player->getID()])){
			$this->main->getServer()->getScheduler()->cancelTask($this->unmutes[$player->getID()]);
		}
	}
	public function setMuted(Player $player, $value){
		$this->mutes[$player->getID()] = $value;
	}
	public function isMuted(Player $player){
		return isset($this->mutes[$player->getID()]) and $this->mutes[$player->getID()];
	}
	public function subscribeToChannel(Player $player, $channel){
		$this->main->getSessioner()->getAttachment($player)->setPermission($channel, true);
	}
	public function unsubscribeFromChannel(Player $player, $channel){
		$this->main->getSessioner()->getAttachment($player)->unsetPermission($channel);
	}
}
