<?php

namespace pemapmodder\legionpe\hub;

class Shop{
	public function __construct(Position $pos, $cost, callable $onConfirmCallback){
		$this->pos = $pos;
		$this->cost = $cost;
		$this->onConfirmCallback = $onConfirmCallback;
	}
	public function purchase(Player $p){
		call_user_func($this->onConfirmCallback, $p);
	}
}
