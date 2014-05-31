<?php

namespace pemapmodder\smg;

use pocketmine\Player;

class ReportList{
	/**
	 * All report objects are stored here for reference
	 * @var Report[]
	 */
	protected $fullList = [];
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
		$this->fullList[$report->getID()] = $report;
	}
	public function read(Player $player){
		if($this->reading[strtolower($player->getName())] instanceof Report){
			return $this->reading[strtolower($player->getName())];
		}
		$this->reading[strtolower($player->getName())] = array_shift($this->unreadReports);
		if(!($this->reading[strtolower($player->getName())] instanceof Report)){
			return null;
		}
		$report = $this->read($player);
		$report->setViewer($player);
		return $report;
	}
	public function markRead(Player $player){
		$report = $this->read($player);
		$this->readReports[$report->getID()] = $report;
		$report->read();
		$this->reading[strtolower($player->getName())] = null;
	}
	public function markResolved(Player $player){
		$report = $this->read($player);
		$this->readReports[$player->getID()] = $report;
		$report->resolve();
		$this->reading[strtolower($player->getName())] = null;
	}
	public function markWarned(Player $player, $flags){
		$report = $this->read($player);
		$this->readReports[$player->getID()] = $report;
		$report->warn($flags);
		$this->reading[strtolower($player->getName())] = null;
	}
	public function get($id){
		return $this->fullList[$id];
	}
	public function getFullList(){
		return $this->fullList;
	}
}
