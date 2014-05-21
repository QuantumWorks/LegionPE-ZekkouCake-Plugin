<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as Loc;
use pemapmodder\legionpe\geog\Position as MyPos;
use pemapmodder\legionpe\mgs\pvp\Pvp;
use pemapmodder\legionpe\mgs\pk\Parkour as Pk;
use pemapmodder\legionpe\mgs\spleef\Main as Spleef;
use pemapmodder\legionpe\mgs\ctf\Main as CTF;

use pemapmodder\utils\CallbackPluginTask;
use pemapmodder\utils\CallbackEventExe;
use pemapmodder\utils\PluginCmdExt;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\RemoteConsoleCommandSender as RCon;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\permission\DefaultPermissions as DP;
use pocketmine\permission\Permission;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

/**
 * Responsible for player auth sessions, teams selection, databases, main commands, permissions, config files and events top+base backup handling
 */
class HubPlugin extends PluginBase implements Listener{
	const CURRENT_VERSION = 0;
	const V_INITIAL = 0;

	const REGISTER	= 0b00010;
	const HUB		= 0b01000; // I consider hub as a NOBLE minigame
	const PVP		= 0b01001; // KitPvP
	const PK		= 0b01010; // Parkour
	const SPLEEF	= 0b01101; // Touch-Spleef
	const CTF		= 0b01110; // Capture The Flag
	// const BG		= 0b01111; // Build and Guess
	const ON		= 0b10111;
	const LOGIN		= 0b11000;
	const LOGIN_MAX	= 0b11111;

	public $statics = array();
	public $sessions = array();
	protected $tmpPws = array();
	public $dbs = array();
	public $teams = array();
	public $config;
	protected $disableListeners = array();
	public function onEnable(){
		console(TextFormat::AQUA."Initializing Hub", false);
		$time = microtime(true);
		$this->path = $this->getServer()->getDataPath()."Hub/";
		@mkdir($this->path);
		$this->playerPath = $this->path."players/";
		@mkdir($this->playerPath);
		echo ".";
		$this->initConfig();
		echo ".";
		$this->initPerms();
		echo ".";
		$this->initObjects();
		echo ".";
		$this->registerHandles();
		echo ".";
		$this->initCmds();
		echo ".";
		$this->initRanks();
		echo TextFormat::toANSI(TextFormat::GREEN." Done! (".(1000 * (microtime(true) - $time))." ms)").PHP_EOL;
	}
	public function onDisable(){
		console(TextFormat::AQUA."Finalizing Hub", false);
		$time = microtime(true);
		$this->config->save();
		foreach($this->disableListeners as $l){
			echo ".";
			call_user_func($l);
		}
		$time = microtime(true) - $time;
		$time *= 1000;
		echo TextFormat::toANSI(TextFormat::GREEN." Done! ($time ms)".TextFormat::RESET);
		echo PHP_EOL;
	}
	public function addDisableListener(callable $callback){
		$this->disableListeners[] = $callback;
	}
	protected function initObjects(){ // initialize objects: Team, Hub, other minigames
		Hub::init();
		Team::init();
		Pvp::init();
		Pk::init();
		Spleef::init();
		CTF::init();
	}
	protected function initPerms(){ // initialize core permissions
		$root = DP::registerPermission(new Permission("legionpe", "Allow using all LegionPE commands and utilities"));
		// minigames
		$mgs = DP::registerPermission(new Permission("legionpe.mg", "Allow doing actions in minigames"), $root);
		// commands
		$cmd = DP::registerPermission(new Permission("legionpe.cmd"), $root);
		$pcmds = DP::registerPermission(new Permission("legionpe.cmd.players", "Allow using player-spawn-despawn-related commands"), $cmd);
		foreach(array("show", "hide") as $act)
			DP::registerPermission(new Permission("legionpe.cmd.players.$act", "Allow using command /$act", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.auth", "Allow using command /auth", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.mg.quit", "Allow using command /quit", Permission::DEFAULT_FALSE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.mg.stat", "Allow viewing statistics using command", Permission::DEFAULT_FALSE), $cmd);
		$chat = DP::registerPermission(new Permission("legionpe.cmd.chat", "Allow using command /chat", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.chat.mandatory", "Allow sending mandatory chat using command /chat mand", Permission::DEFAULT_OP), $chat);
		$ch = DP::registerPermission(new Permission("legionpe.cmd.chat.ch", "Allow using subcommand /chat ch", Permission::DEFAULT_TRUE), $chat);
		DP::registerPermission(new Permission("legionpe.cmd.chat.ch.all", "Allow using subcommand /chat ch bypassing minigame session limitations", Permission::DEFAULT_OP), $ch);
		DP::registerPermission(new Permission("legionpe.cmd.chat.mute", "Allowing using subcommand /chat mute", Permission::DEFAULT_TRUE), $chat);
	}
	protected function initConfig(){
		$prefixes = array("fighter", "killer", "danger", "hard", "beast", "elite", "warrior", "knight", "boss", "addict", "unstoppable", "pro", "hardcore", "master", "legend", "god");
		$diff = 0;
		$last = 0;
		$pfxs = array();
		foreach($prefixes as $pfx){
			$diff += 25;
			$last += $diff;
			$pfxs[$pfx] = $last;
		}
		$this->config = new Config($this->getServer()->getDataPath()."Hub/general-config.json", Config::JSON, array(
			"config-version-never-edit-this"=>self:: CURRENT_VERSION,
			"kitpvp"=>array(
				"prefixes"=>$pfxs,
				"auto-equip"=>array(
					"fighter"=>array(
						"inv"=>array(
							array(267, 0, 1),
							array(360, 0, 32)
						),
						"arm"=>array(306, 299, 300, 309)
					),
				),
				"classes"=>array(
					"player"=>array("fighter", "healer"),
					"donater"=>array("fighter", "healer", "blahblah")
				),
				"top-kills"=>array(
					"Avery Black"=>4,
					"Cindy Donalds"=>3,
					"Elvin Farmer"=>2,
					"Gregor Hill"=>1,
					"Ivan Jones"=>0,
				),
			),
			"spleef"=>array(
				"chances"=>array(
					"player"=>45,
					"donater"=>50,
					"vip"=>55,
					"vip-plus"=>60,
					"vip-plus-plus"=>65,
					"premium"=>70,
					"sponsor"=>75,
					"staff"=>55,
				),
			),
			"parkour"=>array(
				"stats"=>array(
					"easy"=>3, // the fact is, many people told me they finished xD
					"medium"=>3,
					"hard"=>3,
					"extreme"=>3,
				),
			),
		));
	}
	protected function registerHandles(){ // register events
		foreach(array("PlayerJoin", "PlayerChat", "EntityArmorChange", "EntityMove", "PlayerInteract", "PlayerCommandPreprocess", "PlayerLogin", "PlayerQuit") as $e)
			$this->addHandler($e);
	}
	protected function addHandler($event){ // local add handler function
		$this->getServer()->getPluginManager()->registerEvent(
				"pocketmine\\event\\".substr(strtolower($event), 0, 6)."\\".$event."Event", $this,
				EventPriority::HIGHEST, new CallbackEventExe(array($this, "evt")), $this, false);
	}
	public function initCmds(){ // register commands
		if("quit" === "quit"){
			$cmd = new PluginCommand("quit", $this);
			$cmd->setUsage("/quit");
			$cmd->setDescription("Quit the current minigame, if possible");
			$cmd->setPermission("legionpe.cmd.mg.quit");
			$cmd->register($this->getServer()->getCommandMap());
		}
		if("stat" === "stat"){
			$cmd = new PluginCommand("stat", $this);
			$cmd->setDescription("Show your stats in the current minigame, if possible");
			$cmd->setUsage("/stat [args...] (differs in different minigames)");
			$cmd->setPermission("legionpe.cmd.mg.stat");
			$cmd->register($this->getServer()->getCommandMap());
		}
		if("show" === "show"){
			$cmd = new PluginCommand("show", $this);
			$cmd->setUsage("/show <invisible player|all>");
			$cmd->setDescription("Attempt to show an invisible player");
			$cmd->setPermission("legionpe.cmd.players.show");
			$cmd->register($this->getServer()->getCommandMap());
		}
		if("hide" === "hide"){
			$cmd = new PluginCommand("hide", $this);
			$cmd->setUsage("/hide <player to hide>");
			$cmd->setDescription("Make a player invisible to you");
			$cmd->setPermission("legionpe.cmd.players.hide");
			$cmd->register($this->getServer()->getCommandMap());
		}
		if("auth" === "auth"){
			$cmd = new PluginCommand("auth", $this);
			$cmd->setUsage("/auth <ip|help> [args ...]");
			$cmd->setDescription("Auth-related commands");
			$cmd->setPermission("legionpe.cmd.auth");
			$cmd->register($this->getServer()->getCommandMap());
		}
		if("chat" === "chat"){
			$cmd = new PluginCmdExt("chat", $this, Hub::get());
			$cmd->setUsage("/chat <ch|mute|help>");
			$cmd->setDescription("Chat-related commands");
			$cmd->setPermission("legionpe.cmd.chat");
			$cmd->register($this->getServer()->getCommandMap());
		}
	}
	public function onCommand(CommandSender $issuer, Command $cmd, $label, array $args){ // handle commands
		switch($cmd->getName()){
		case "show":
			if(!($issuer instanceof Player)){ // yell at whoever typed this, if not a player
				$issuer->sendMessage("You are not supposed to see any players here!");
				return true;
			}
			if(@strtolower(@$args[0]) !== "all" and !(($p = Player::get(@$args[0])) instanceof Player)){ // show usage, if invalid args
				return false;
			}
			if(strtolower($args[0]) !== "all"){ // spawn a player, if player specified
				if($p->level->getName() === $issuer->level->getName())
					$p->spawnTo($issuer);
				else $issuer->sendMessage($p->getDisplayName()." is not in your world!");
			}
			else{ // spawn all players, if player not specified
				foreach(Player::getAll() as $p){
					if($p->level->getName() === $issuer->level->getName())
						$p->spawnTo($issuer);
				}
			}
			break;
		case "hide":
			if(!($issuer instanceof Player)){ // yell at whoever typed this, if not a player
				$issuer->sendMessage("You are not supposed to see any players here!");
				return true;
			}
			if(!isset($args[0]) or !(($p = Player::get($args[0])) instanceof Player)){ // show usage, if invalid args
				return false;
			}
			$p->despawnFrom($issuer); // operate
			$issuer->sendMessage("{$p->getDisplayName()} is now invisible to you.");
			break;
		case "auth":
			if(!($issuer instanceof Player)){
				$issuer->sendMessage("What? You need to authenticate?");
				if($issuer instanceof RCon) // just too bored? xD
					$isuer->sendMessage("OK, but you don't manage the RCON password here, right?");
				return true;
			}
			$subcmd = @array_shift($args);
			switch($subcmd){ // manage subcommand
				case "ip": // ip-auth settings
					if(isset($args[0])){
						if(strtolower($args[0]) === "on"){
							$this->getDb($issuer)->set("ip-auth", $issuer->getAddress());
							$issuer->sendMessage("Your IP-auth is now on with value \"{$issuer->getAddress()}\".");
							break 2;
						}
						if(strtolower($args[0]) === "off"){
							$this->getDb($issuer)->set("ip-auth", false);
							$issuer->sendMessage("Your IP-auth has been turned off.");
							break 2;
						}
					}
					$issuer->sendMessage("Your IP-auth is ".(($s = $this->getDb($issuer)->get("ip-auth")) === false ? "off.":
							"on with value \"$s\"."));
					break;
				case "help":
				case false:
				default:
					$issuer->sendMessage("Usage: /auth <ip|help> [args ...]");
					$issuer->sendMessage("/auth <ip> [on|off|any words]");
					$issuer->sendMessage("/auth <help>");
					break;
			}
			break;
		case "quit":
			Hub::get()->onQuitCmd($issuer, $args);
		case "stat":
			Hub::get()->onStatCmd($issuer, $args);
		}
		return true;
	}
	public function evt(Event $event){ // handle events
		$class = explode("\\", get_class($event));
		$class = $class[count($class) - 1];
		if(is_callable(array($event, "getPlayer"))){ // if is player event, store player into $p
			$p = $event->getPlayer();
		}
		switch(substr($class, 0, -5)){ // handle events
			case "PlayerLogin": // what the **** I put it here for...
				break;
			case "PlayerJoin": // open database, check password (decide (registry wizard / IP auth / password auth))
				// console("[INFO] ".$p->getDisplayName()." entered the game.");
				Hub::get()->setChannel($p, "legionpe.chat.mute.".$p->CID);
				$event->setMessage("");
				$this->openDb($p);
				if($this->getDb($p)->get("pw-hash") === false){ // request register (LegionPE registry wizard), if password doesn't exist
					$this->sessions[$p->CID] = self::REGISTER;
					$p->sendMessage("Welcome to the LegionPE account registry wizard.");
					$p->sendMessage("Step 1:");
					$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
				}
				elseif($this->getDb($p)->get("ip-auth") === $p->getAddress()){ // authenticate, if ip auth enabled and matches
					$p->sendMessage("You have been authenticated by your IP address.");
					$this->sessions[$p->CID] = self::HUB;
					$this->onAuthPlayer($p);
				}
				else{ // request login (normal), if password exists and ip auth not enabled or not matched
					$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
					$this->sessions[$p->CID] = self::LOGIN;
				}
				break;
			case "PlayerChat": // if session is not self::HUB, monitor it. if session is self::HUB, prevent typing password here
				if(($s = $this->sessions[$p->CID]) < self::HUB or $s >= self::LOGIN){ // if not authed
					$event->setCancelled(true);
				}
				elseif($this->getDb($p)->get("pw-hash") === $this->hash($event->getMessage())){ // if authed but is telling password
					$event->setCancelled(true);
					$p->sendMessage("Never talk loudly to others your password!");
				}
				if($s === self::REGISTER){ // request repeat password: registry wizard step 1
					$this->tmpPws[$p->CID] = $event->getMessage();
					$p->sendMessage("Step 2:");
					$p->sendMessage("Please enter your password again to confirm.");
					$this->sessions[$p->CID] ++;
				}
				elseif($s === self::REGISTER + 1){ // check repeated password: registry wizard step 2
					if($this->tmpPws[$p->CID] === $event->getMessage()){ // choose team, if matches password
						$p->sendMessage("The password matches! Type this password into your chat and send it next time you login.");
						$p->sendMessage("LegionPE registry wizard closed!"); // TODO anything else I need to request?
						$this->getDb($p)->set("pw-hash", $this->hash($event->getMessage()));
						$this->sessions[$p->CID]++;
						unset($this->tmpPws[$p->CID]);
						$this->onRegistered($p);
					}
					else{ // if password different
						$p->sendMessage("Password doesn't match! Going back to step 1.");
						$p->sendMessage("Please type your password in the chat.");
						$this->sessions[$p->CID] = self::REGISTER;
					}
				}
				elseif($s >= self::LOGIN){ // check password, if session is waiting login
					$hash = $this->getDb($p)->get("pw-hash");
					if($this->hash($event->getMessage()) === $hash){ // auth, if password matches
						if($this->getDb($p)->get("ip-auth") !== false){ // update IP
							$this->getDb($p)->set("ip-auth", $p->getAddress());
							$p->sendMessage("Your IP address has been updated to \"{$p->getAddress()}\".");
						}
						$this->onAuthPlayer($p);
					}
					else{ // add session, if password doesn't match
						$p->sendMessage("Password doesn't match! Please try again.");
						$this->sessions[$p->CID]++;
						if($s >= self::LOGIN_MAX){ // if reaches maximum trials of login
							$p->sendMessage("You exceeded the max number of trials to login! You are being kicked.");
							$this->getServer()->getScheduler()->scheduleDelayedTask(
									new CallbackPluginTask(array($p, "close"), $this, array("Failing to auth.", "Auth failure"), true), 80);
						}
					}
				}
				break;
			// protect|block player whilst logging in/registering
			case "EntityArmorChange":
			case "EntityMove":
				$p = $event->getEntity();
				if(!($p instanceof Player)){ // only for players, not entities
					break;
				}
			case "PlayerCommandPreprocess":
			case "PlayerInteract":
				if($this->sessions[$p->CID] !== self::HUB){ // disallow logging in
					$event->setCancelled(true);
					$p->sendMessage("Please login/register first!");
				}
				elseif($event instanceof \pocketmine\event\player\PlayerInteractEvent){ // check if is tapping join team signs, if is block touch
					$block = new MyPos($event->getBlock());
					if($block->level->getName() === Loc::hub()->getName()){
						for($i = 0; $i < 4; $i++){
							if($block->equals(Loc::chooseTeamSign($i))){
								if($this->getDb($p)->get("team") === false){
									$team = Team::get($i);
									if(($reason = $team->join($p)) === "SUCCESS"){
										$this->getDb($p)->set("team", $i);
										$p->sendMessage("$reason! You are now a member of team $team!");
										$p->teleport(Loc::spawn());
										$this->onAuthPlayer($p);
									}
									else{
										$p->sendMessage("Failure to join team $team. Reason: $reason");
									}
								}
								break;
							}
						}
					}
				}
				break;
			case "PlayerQuit":
				$this->closeDb($p);
			default:
				console("[WARNING] Event ".get_class($event)." passed to listener at ".get_class()." but not listened to!");
				break;
		}
	}
	public function onRegistered(Player $p){ // set session to self::HUB and choose team, on registry success
		Hub::get()->setChannel($p, "legionpe.chat.mute.".$p->CID);
		$p->teleport(Loc::chooseTeamStd());
		$this->sessions[$p->CID] = self::HUB;
		$p->sendChat("Please select a team.\nSome teams are unselectable because they are too full.\nIf you insist to join those teams, come back later.");
	}
	public function onAuthPlayer(Player $p){ // set session to self::HUB, tp to spawn, ensure tp, call PlayerAuthEvent
		Hub::get()->setChannel($p, "legionpe.chat.general"); // luckily I remembered this :D
		$this->sessions[$p->CID] = self::HUB;
		$p->sendChat("You have successfully logged in into LegionPE!");
		$s = Loc::spawn();
		$p->teleport($s);
		$this->getServer()->getPluginManager()->callEvent(new PlayerAuthEvent($p));
		$this->getServer()->getScheduler()->scheduleDelayedTask(
				new CallbackPluginTask(array($p, "teleport"), $this, array($s), true), 100);
	}
	public function initRanks(){ // initialize ranks
		$def = array(
			"donater"=>array(),
			"vip"=>array(),
			"vip-plus"=>array(),
			"vip-plus-plus"=>array(),
			"premium"=>array(),
			"sponsor"=>array(),
			"staff"=>array("pemapmodder", "lambo", "spyduck"));
		// with reference to http://legionpvp.eu
		$this->ranks = new Config($this->getServer()->getDataPath()."ranks.yml", Config::YAML, $def);
	}
	// utils //
	public function getRank($p){ // get the lowercase rank of a player
		foreach($this->ranks->getAll() as $rank=>$names){
			if(in_array(strtolower($p->getName()), $names))
				return $rank;
		}
		return "player";
	}
	public final static function getPrefixOrder(){ // get the order of prefixes as well as filters
		return array("rank"=>"all", "team"=>"all", "kitpvp"=>"pvp", "kitpvp-rank"=>"pvp", "kitpvp-kills"=>"pvp", "parkour"=>"pk");
	}
	public function logp(Player $p, $msg){
		file_put_contents($this->playerPath.$p->getAddress().".log", $log. PHP_EOL);
	}
	protected function openDb(Player $p){ // open and initialize the database of a player
		@touch($this->playerPath.$p->getAddress().".log");
		$this->logp($p, "#Log file of player ".$p->getDisplayName()." and possibly other names with the same IP address: ".$p->getAddress());
		$config = new Config($this->playerPath.strtolower($p->getName().".yml"), Config::YAML, array(
	 	 	"config-version-never-edit-this" => self:: CURRENT_VERSION,
			"pw-hash" => false, // I don't care whether they are first time or not, just care they registered or not
			"ip-auth" => false,
			"coins"=>200,
			"prefixes" => array(
				"kitpvp"=>"",
				"kitpvp-kills"=>"",
				"parkour"=>"",
				"kitpvp-rank"=>"",
				"rank"=>"",
			),
			"kitpvp"=>array("kills"=>0, "deaths"=>0, "class"=>"fighter"),
			"parkour"=>array(),
			"spleef"=>array("wins"=>0, "unwons"=>0),
			"ctf"=>array(),
			"team" => false,
			"mute" => false
		));
		$r = ($rank = $this->getRank($p)) === "player" ? "":ucfirst($rank);
		$pfxs = $config->get("prefixes");
		$pfxs["rank"] = $r;
		$config->set("prefixes", $pfxs);
		$config->save();
		$path = $this->getServer()->getDataPath()."SKC-Rewrite/player-databases/".strtolower($p->getName()[0])."/{$p->getName()}.txt";
		if(($yaml = @file_get_contents($path)) !== false){
			$data = yaml_parse($yaml);
			$i = $config->get("kitpvp");
			$i["kills"] = $data["kills"];
			$i["deaths"] = $data["deaths"];
			$config->set("kitpvp", $i);
			$config->set("mute", @$data["mute"]);
			$config->save();
		}
		$this->dbs[strtolower($p->getName())] = $config;
	}
	protected function closeDb(Player $p){ // save and finalize the database of a player
		$this->dbs[strtolower($p->getName())]->save();
		unset($this->dbs[strtolower($p->getName())]);
	}
	public function getDb($p){ // get the database of a player
		if(is_string($p))
			$iname = strtolower($p);
		else $iname = strtolower($p->getName());
		return @$this->dbs[$iname];
	}
	protected function hash($string){ // top secret: password hash (very safe hash indeed... so much salt...)
		$salt = "";
		for($i = strlen($string) - 1; $i >= 0; $i--)
			$salt .= $string{$i};
		$salt = @crypt($string, $salt);
		return bin2hex((0xdeadc0de * hash(hash_algos()[17], $string.$salt, true)) ^ (0x6a7e1ee7 * hash(hash_algos()[31], strtolower($salt).$string, true)));
	}
	public function getSession(Player $p){
		return $this->sessions[$p->CID];
	}
	public function getTeam($i){
		return $this->teams[Team::evalI($i)];
	}
	public static $instance = false;
	public static function get(){ // get instance
		return Server::getInstance()->getPluginManager()->getPlugin("LegionPE_Delta");
	}
}
