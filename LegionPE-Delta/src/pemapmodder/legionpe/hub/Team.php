<?php

namespace pemapmodder\legionpe\hub {
	use pemapmodder\legionpe\geog\RawLocs as RL;
	use pemapmodder\utils\DummyPlugin;
	use pemapmodder\utils\CallbackPluginTask;

	use pocketmine\Player;
	use pocketmine\Server;
	use pocketmine\block\Block;

	class Team implements \ArrayAccess{
		// static
		public static function addPoints($i, $pts){
			self::get($i)->config["points"] += $pts;
		}
		/**
		 * @param int|Player $i
		 * @return bool|int Also returns null when unexpected argument type passed, but this should not be documented
		 */
		public static function evalI($i){
			if(is_int($i)) $i &= 0b11;
			elseif($i instanceof Player) $i = HubPlugin::get()->getDb($i)->get("team");
			else{
				trigger_error("Unexpected argument 1 (".print_r($i, true).") passed to ".get_class()."::evalI($i)", E_USER_ERROR);
				return null;
			}
			return $i;
		}
		/**
		 * @return Team[]
		 */
		public static function getAll(){
			$r = array();
			for($i = 0; $i < 4; $i++)
				$r[$i] = HubPlugin::get()->getTeam($i);
			return $r;
		}
		/**
		 * @param $i
		 * @return Team
		 */
		public static function get($i){
			return HubPlugin::get()->getTeam($i);
		}
		public static function init(){
			@mkdir(Server::getInstance()->getDatapath()."hub/teams/");
			for($i = 0; $i < 4; $i++){
				HubPlugin::get()->teams[$i] = new self($i);
			}
			Server::getInstance()->getScheduler()->scheduleRepeatingTask(new CallbackPluginTask(array(get_class(), "updateScoreBars"), HubPlugin::get()), 600);
		}
		public static function canJoin($team){
			$scores = array();
			foreach(self::getAll() as $t){
				$scores[$t->getTeam()] = $t->config["members-cnt"];
			}
			$ts = self::get($team)->config["members-cnt"];
			$max = max($scores);
			$percent = ($max - $ts) / $ts * 100;
			return $percent <= 5;
		}
		public static function updateSigns(){
			for($i = 0; $i < 4; $i++){
				if(self::canJoin($i)){
					DummyPlugin::getTile(RL::chooseTeamSign($i))->setText("Tap me to join", "TEAM ".strtoupper(self::getTeam($i)->config["name"]));
				}
				else{
					DummyPlugin::getTile(RL::chooseTeamSign($i))->setText("TEAM ".strtoupper(self::get($i)->config["name"]), "is now full.", "Come back later", "or join others");
				}
			}
		}
		public static function updateScoreBars(){
			$scores = array();
			for($i = 0; $i < 4; $i++){
				$scores[$i] = self::get($i)->config["points"];
			}
			$max = max($scores);
			for($i = 0; $i < 4; $i++){
				$percent = max(0, $scores[$i]) / $max * 100;
				RL::teamScoreBar($i, $percent)->setBlocks(Block::get(35, self::get($i)->config["color-meta"]));
			}
			console("[INFO] Hub score bars have been updated.");
		}
		// non-static
		public $config = array();
		public function __construct($i){
			$this->team = $i;
			$this->server = Server::getInstance();
			$path = $this->server->getDataPath()."hub/teams/team-$i.yml";
			$this->path = $path;
			Server::getInstance()->getScheduler()->scheduleRepeatingTask(new CallbackPluginTask(array($this, "save"), HubPlugin::get()), 1200);
			if(is_file($path)){
				$this->config = yaml_parse(file_get_contents($path));
			}
			else{
				$names = array("magma", "lapiz", "lilac", "lime");
				$this->config["name"] = $names[$i];
				$metas = array(1, 3, 10, 5);
				$this->config["color-meta"] = $metas[$i];
				$this->config["points"] = 1000;
				$this->config["members-cnt"] = 10;
				file_put_contents($path, yaml_emit($this->config));
			}
		}
		public function save(){
			file_put_contents($this->path, yaml_emit($this->config));
		}
		/**
		 * @param Player $p
		 * @return string "SUCCESS" or reason for join failure
		 */
		public function join(Player $p){
			if(HubPlugin::get()->getDb($p)->get("team") !== false){
				return "Already in a team";
			}
			if(self::canJoin($this->team)){
				$this->config["members-cnt"]++;
				self::updateSigns();
				return "SUCCESS";
			}
			return "team is full";
		}
		public function getTeam(){
			return $this->team;
		}
		/**
		 * IDK why I must document this to suppress PHPStorm's warning
		 * @return string
		 */
		public function __toString(){
			return $this->config["name"];
		}
		public function offsetExists($key){
			return array_key_exists($key, $this->config);
		}
		public function offsetUnset($key){
			unset($this->config[$key]);
		}
		public function offsetGet($key){
			return $this->config[$key];
		}
		public function offsetSet($key, $value){
			$this->config[$key] = $value;
			$this->save();
		}
	}
}
namespace{
	if(!function_exists("console")){
		function console($msg){
			\pemapmodder\legionpe\hub\HubPlugin::get()->getLogger()->info($msg);
		}
	}
}
