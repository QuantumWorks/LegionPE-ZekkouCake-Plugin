<?php

namespace pemapmodder\smg;


class ReportList{
	/**
	 * @var Report[] read reports
	 */
	protected $readReports = [];
	/**
	 * @var Report[]
	 */
	protected $unreadReports = [];
	/**
	 * @var null|Report[] the currently reading report
	 */
	public $reading = [];
	public function add(Report $report){
		$this->unreadReports[$report->getID()] = $report;
	}
	public function read(Player $player){
		if($this->reading[strtolower($player->getName())] instanceof Report){
			return $this->reading[strtolower($player->getName())];
		}
		$this->reading[strtolower($player->getName())] = array_shift($this->unreadReports);
		return $this->read($player);
	}
}
