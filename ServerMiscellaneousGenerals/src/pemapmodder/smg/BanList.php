<?php

namespace pemapmodder\smg;

class BanList{
	const EXPIRY_DAYS = 28;
	public function __construct($path){
		if(!is_file($path)){
			$this->save();
		}
		$this->load();
	}
	public function warn($address, $points, $expiry = self::EXPIRY_DAYS, $banDays = false){
		if(!isset($this->data[$address])){
			$this->data[$address] = [];
		}
		if(!isset($this->data[$address]["issues"])){
			$this->data[$address]["issues"] = [];
		}
		$this->data[$address]["issues"][] = [$points, time() + $expiry * 60 * 60 * 24];
		$points = $this->getPoints($address);
		if($points >= 3){
			$days = 1;
		}
		if($points >= 6){
			$days = 3;
		}
		if($points >= 9){
			$days = 6;
		}
		if($points >= 12){
			$days = 10;
		}
		if($points >= 15){
			$days = 15;
		}
		if($banDays !== false){
			$days = $banDays;
		}
		$this->data[$address]["lift"] = time() + $days * 60 * 60 * 24;
	}
	public function isBanned($address, &$secsLeft = 0){
		if(!isset($this->data[$address]["lift"])){
			return false;
		}
		$lift = $this->data[$address]["lift"];
		$secsLeft = $lift - time();
		return $secsLeft >= 0;
	}
	public function getPoints($address){
		$points = 0;
		foreach($this->data[$address]["issues"] as $issue){
			if(time() > $issue[1]){
				$points += $issue[0];
			}
		}
		return $points;
	}
	public function __destruct(){
		$this->save();
	}
	protected function load(){
		$this->data = [];
		$this->data = json_decode(file_get_contents($this->path));
	}
	public function save(){
		file_put_contents($this->path, json_encode($this->data));
	}
}
