<?php

namespace pemapmodder\smg;

use pocketmine\Server;

class BanList{
	const EXPIRY_LENGTH = 2592000;
	public function __construct($path){
		$this->path = $path;
		$this->data = json_decode(file_get_contents($path));
		$this->server = Server::getInstance();
	}
	public function finalize(){
		file_put_contents($this->path, json_encode($this->data));
	}
	/**
	 * @param string $player
	 * @param int $points
	 * @param number $days
	 */
	public function warn($player, $points, $days){
		if(!isset($this->data[$player])){
			$this->data[$player] = ["current ban expiry" => 0, "issues" => []];
		}
		$this->data[$player]["issues"][] = ["points" => $points, "expiry" => time() + $days * 24 * 60 * 60];
		$this->updateBan($player);
	}
	/**
	 * @param string $player
	 * @return bool
	 */
	public function canLogin($player){
		$this->updateBan($player);
		if(isset($this->data[$player]) and $this->data["current ban expiry"] >= time()){
			return false;
		}
		return true;
	}
	/**
	 * @param string $player
	 */
	public function updateBan($player){
		$secs = $this->checkBan($this->getWarnPoints($player));
		if($secs > 0){
			$this->data[$player]["current ban expiry"] = time() + $secs;
		}
	}
	protected function checkBan($pts){
		switch(true){
			case $pts >= 10:
				return self::EXPIRY_LENGTH;
			case $pts >= 8:
				return 10 * 24 * 60 * 60;
			case $pts >= 6:
				return 6 * 24 * 60 * 60;
			case $pts >= 4:
				return 3 * 24 * 60 * 60;
			case $pts >= 2:
				return 1 * 24 * 60 * 60;
			default:
				return 0;
		}
	}
	/**
	 * @param $player
	 * @return int
	 */
	public function getWarnPoints($player){
		if(!isset($this->data[$player])){
			return 0;
		}
		$current = 0;
		foreach($this->data[$player]["issues"] as $issue){
			if(time() < $issue["expiry"]){
				$current += $issue["poitns"];
			}
		}
		return $current;
	}
}
