<?php

namespace legionpe;

use pocketmine\Player;

class PlayerConfig{
	private $main;
	/** @var mixed[] */
	private $data;
	/** @var string */
	private $name, $path;
	private $lastLastJoin;
	public function __construct(Player $player, Main $main){
		$this->main = $main;
		$this->path = $main->getDataFolder()."players/".strtolower($player->getName()).".json";
		$this->name = strtolower($player->getName());
		if(!is_file($this->path)){
			stream_copy_to_stream($main->getResource("dummy player.json"), fopen($this->path, "wb"));
		}
		$this->reload();
		$this->lastLastJoin = $this->getLastjoin();
		$this->updateLastJoin(time());
	}
	public function updateLastJoin($time){
		$this->set("last-join", $time);
	}
	public function updateLastAuth($time){
		$this->set("last-auth", $time);
	}
	public function &get($k){
		return $this->data[$k];
	}
	public function set($k, $v){
		$this->data[$k] = $v;
	}
	public function &getSub(){
		$args = func_get_args();
		$data = $this->data;
		while(count($args) > 0){
			$data = $data[array_shift($args)];
		}
		return $data;
	}
	public function getName(){
		return $this->name;
	}
	public function &getCaseUsernames(){
		return $this->data["case-usernames"];
	}
	public function touchCaseUsername($name){
		if(!in_array($name, $this->getCaseUsernames())){
			$this->data["case-usernames"][] = $name;
		}
	}
	public function &getTeam(){
		return $this->get("team");
	}
	public function setTeam($t){
		$this->set("team", $t);
	}
	public function &getCoins(){
		return $this->get("coins");
	}
	public function addCoins($c){
		$this->data["coins"] += $c;
	}
	public function getLastLastJoin(){
		return $this->lastLastJoin;
	}
	public function getLastJoin(){
		return $this->get("last-join");
	}
	public function touchIP($ip){
		if(!$this->hasIP($ip)){
			$this->data["ips"][] = $ip;
		}
	}
	public function hasIP($ip){
		return in_array($ip, $this->data["ips"]);
	}
	/**
	 * @param string|bool $ip
	 */
	public function setAuthIP($ip){
		$this->set("ip-auth", $ip);
	}
	public function getAuthIP(){
		return $this->get("ip-auth");
	}
	public function getPassword(){
		return $this->data["password"];
	}
	public function savePassword($pw){
		$this->data["password"] = $this->hash($pw);
	}
	public function matchPassword($pw){
		return $this->getPassword() === $this->hash($pw);
	}
	private function hash($pw){
		$salt = $this->getName();
		return bin2hex(hash("sha512", $pw.$salt, true) ^ hash("whirlpool", $salt.$pw, true));
	}
	public function reload(){
		$this->data = json_decode(file_get_contents($this->path));
	}
	public function save(){
		file_put_contents($this->path, json_encode($this->data), LOCK_EX);
	}
	public function __destruct(){
		$this->save();
	}
}
