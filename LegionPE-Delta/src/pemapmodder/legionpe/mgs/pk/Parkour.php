<?php

namespace pemapmodder\legionpe\mgs\pk;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\hub\Team;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\CallbackEventExe;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\SignPost;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\permission\DefaultPermissions as DP;
use pocketmine\permission\Permission as Perm;

class Parkour extends MgMain implements Listener{
	protected $prefixes = array(
		0=>"", // fix array_search() bug; hope it does xD
		1=>"easy",
		2=>"medium",
		3=>"hard",
		4=>"extreme"
	);
	protected $attachments = array();
	public function __construct(){
		$this->server = Server::getInstance();
		$this->hub = HubPlugin::get();
		$pm = $this->server->getPluginManager();
		// events
		$pm->registerEvent("pocketmine\\event\\entity\\EntityMoveEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onMove")), HubPlugin::get());
		$pm->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onInteract")), HubPlugin::get());
		// permission
		DP::registerPermission(new Perm("legionpe.cmd.mg.pk", "Allow using parkour commands", Perm::DEFAULT_FALSE), $pm->getPermission("legionpe.cmd.mg"));
	}
	public function onMove(EntityMoveEvent $event){
		$p = $event->getEntity();
		if($p instanceof Player){
			if($p->getLevel()->getName() === "world_parkour" and ($p->x < 77 and $p->x > 40) and ($p->z < 100 and $p->z > 30)){
				if($p->y <= RawLocs::fallY()){
					$p->teleport(new Vector3($p->x, 73, 67));
					Team::get($this->hub->getDb($p)->get("team"))["points"]--; // Am I sure?
				}
			}
		}
	}
	public function getStats(Player $player, array $args = []){
		return "~~~~~~~~Parkour stats~~~~~~~~\n".str_replace(PHP_EOL, "\n", yaml_emit($this->hub->config->get("parkour")->get("stats")))."\n~~~~~~~~Parkour stats~~~~~~~~";
	}
	public function onInteract(PlayerInteractEvent $event){
		if($event->getBlock() instanceof SignPost){
			$event->setCancelled(true);
			if(($pfx = RawLocs::signPrefix($event->getBlock())) !== false){
				$config = HubPlugin::get()->getDb($event->getPlayer());
				$prefixes = $config->get("prefixes");
				$original = $prefixes["parkour"];
				$origIndex = array_search($prefixes["parkour"], $this->prefixes);
				Team::get($event->getPlayer())["points"] += ($origIndex - 1);
				if($origIndex >= array_search($pfx, $this->prefixes)){
					$event->getPlayer()->sendMessage("You can't set your prefix to a lower level!\nYou (might have) spent so much effort getting \"".$prefixes["parkour"]."\".\nWhy give it up?");
					return;
				}
				$prefixes["parkour"] = $pfx;
				$config->set("prefixes", $prefixes);
				$config->save();
				$db = $this->hub->config->get("parkour");
				if($origIndex !== 0)
					$db["stats"][$original]--;
				$db["stats"][$pfx]++;
			}
		}
	}
	public function onJoinMg(Player $p){
		$this->attachments[$p->getID()] = $p->addAttachment($this->hub, "legionpe.cmd.mg.pk", true);
	}
	public function onQuitMg(Player $p){
		$p->removeAttachment($this->attachments[$p->getID()]);
		unset($this->attachments[$p->getID()]);
	}
	public function getName(){
		return "Parkour";
	}
	public function getSessionId(){
		return HubPlugin::PK;
	}
	public function getDefaultChatChannel(Player $p, $t){
		return "legionpe.chat.pk.public";
	}
	public function getSpawn(Player $player, $t){
		return RawLocs::pkSpawn();
	}
	public function isJoinable(Player $player, $t){
		return true;
	}
	public static $i = false;
	public static function get(){
		return HubPlugin::get()->statics[get_class()];
	}
	public static function init(){
		HubPlugin::get()->statics[get_class()] = new static();
	}
}
