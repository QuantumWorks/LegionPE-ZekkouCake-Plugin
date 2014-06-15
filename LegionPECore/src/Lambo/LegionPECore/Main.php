<?php

namespace Lambo\LegionPECore;

use pemapmodder\legionpe\hub\HubPlugin;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\tile\Tile;

class Main extends PluginBase implements CommandExecutor,Listener{
	/** @var Config */
	public $ranks;
    private $players=array();
    private $mutedPlayers=array();
    private $noMsgPlayers=array();
    private $killStreaks=array();
    private $respawnPos=array();

	public function onEnable(){
        $this->getLogger()->info(TextFormat::LIGHT_PURPLE."LegionPECore loaded.");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if(file_exists("worlds/world_pvp")) $this->getServer()->loadLevel("world_pvp");
        if(file_exists("worlds/world_spleef")) $this->getServer()->loadLevel("world_spleef");
        if(file_exists("worlds/world_parkour")) $this->getServer()->loadLevel("world_parkour");
        @mkdir("player-databases/");
        @mkdir("player-databases/players/");
        $this->ranks = HubPlugin::get()->getConfig();#new Config("ranks.yml",Config::YAML,array("donater"=>array(),"donater+"=>array(),"vip"=>array(),"vip+"=>array(),"trial-moderator"=>array(),"moderator"=>array(),"developer"=>array(),"admin"=>array(),"owner"=>array()));
	}

	public function onDisable(){
        $this->getLogger()->info(TextFormat::LIGHT_PURPLE."LegionPECore disabled.");
	}

    /*public function onDeath(PlayerDeathEvent $event){
    if($player->getLevel()->getName()=="world_pvp" and $victim->getLevel()->getName()=="world_pvp"){
        $this->respawnPos[$event->getPlayer()->getName()]["pos"]=new Position($this->server->getLevel("world_pvp")->getSafeSpawn());
        $victim = $this->;
        $player = $this->getPlayer();
        $this->set($player,"pvp-deaths",$this->get($player,"pvp-deaths")+1);
        $this->set($victim,"pvp-kills",$this->get($victim,"pvp-kills")+1);
        $this->killStreaks[$player->getName()]=0;
        $this->killStreaks[$victim->getName()]=$this->killStreaks[$victim->getName()]+1;
        if($this->killStreaks[$victim->getName()]==3 or $this->killStreaks[$victim]==5 or $this->killStreaks[$victim->getName()]==10 or $this->killStreaks[$victim->getName()]==20){
            foreach($this->server->getOnlinePlayers() as $players){
                if($players->getLevel() == $victim->getLevel()){
                    if(!in_array($players->getName(),$this->noMsgPlayers)){
                        if($this->killStreaks[$victim->getName()]==3) $players->sendMessage($victim->getName()." has got a TRIPLE KILL!");
                        if($this->killStreaks[$victim->getName()]==5) $players->sendMessage($victim->getName()." has got a killstreak of 5!");
                        if($this->killStreaks[$victim->getName()]==10) $players->sendMessage($victim->getName()." has got a killstreak of 10!");
                        if($this->killStreaks[$victim->getName()]==20) $players->sendMessage($victim->getName()." has got a killstreak of 30!");
                    }
                }
            }
        }
        $player->sendMessage("You have been killed by ".$victim->getName()."!\nYou have now have a total of ".$this->get($player,"pvp-deaths")." deaths.");
        $victim->sendMessage("You have killed ".$player->getName()."!\nYou have now have a total of ".$this->get($victim,"pvp-kills")." kills.\nYour killstreak is ".$this->killStreaks[$victim->getName()].".");
        $victim->heal($this->getHealHalfHearts($victim));
    }else $this->respawnPos[$event->getPlayer()->getName()]["pos"]=new Position($this->server->getLevel("world")->getSafeSpawn());
    }*/

    /**
     * @param BlockBreakEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event){
        if($event->getPlayer()->getName()=="Lambo" or $event->getPlayer()->getName()=="SpyDuck"){
            $event->setCancelled(false);
        }else $event->setCancelled(true);
    }

    /**
     * @param PlayerRespawnEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onRespawn(PlayerRespawnEvent $event){
        $event->setRespawnPosition($this->respawnPos[$event->getPlayer()->getName()]["pos"]);
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlaceBreak(BlockPlaceEvent $event){
        if($event->getPlayer()->getName()=="Lambo" or $event->getPlayer()->getName()=="SpyDuck"){
            $event->setCancelled(false);
        }else $event->setCancelled(true);
    }

    /**
     * @param PlayerChatEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onChat(PlayerChatEvent $event){
        $msg = $event->getMessage();
        $player = $event->getPlayer();
//        $message = $this->getMsg($player,$msg); // note: Prefix API?
        foreach($this->getServer()->getOnlinePlayers() as $players){
            if($players->getLevel() == $player->getLevel()){
                if(!in_array($players->getName(),$this->noMsgPlayers)){
//                    $players->sendMessage($message);
                }
            }
        }
        $event->setCancelled(true);
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onQuit(PlayerQuitEvent $event){
        if($event->getPlayer()->getLevel()->getName()=="world") $event->getPlayer()->teleport($this->getServer()->getLevel("world")->getSafeSpawn());
        $event->setQuitMessage("");
    }
    
    /**
     * @param PlayerJoinEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onJoin(PlayerJoinEvent $event){// I had already done this
//        $event->setJoinMessage("");
//        $player = $event->getPlayer();
//        $username = $player->getName();
//        if(file_exists("player-databases/players/".$username.".txt")){
//            $player->sendMessage("===============================\nWelcome back to Legion PE, ".$username."!\nYou are in the level ".$player->getLevel()->getName()."\nYour coins: ".$this->get($player,"coins")."\n===============================");
//        }
//        else{
//            $this->createConfig($player);
//            $this->getLogger()->info(TextFormat::LIGHT_PURPLE."Creating config of ".$username.".");
//            $player->sendMessage("\n===============================\nWelcome to Legion PE, ".$username."!\nYou can start playing straight away, or look at\nthe tutorial by typing /tutorial\n===============================");
//        }
//
//        if(file_exists("player-databases/spleef-database/".$username.".txt")){
//            $c = $this->get($player,null);
//            $d = new Config("player-databases/spleef-database/".$username.".txt", Config::YAML, array());
//            $player->sendMessage("Converting old spleef config...");
//            $c->set("spleef-wins",$d->get("wins"));
//            $c->set("spleef-loses",$d->get("loses"));
//            $c->set("spleef-WLR",($d->get("wins") / $d->get("loses")));
//            $player->sendMessage("Your spleef config has been converted!");
//            unlink("player-databases/spleef-database/".$username.".txt");
//        }
//        if(file_exists("SKC-Rewrite/player-databases/".strtolower($username[0])."/".strtolower($username).".txt")){
//            $c = $this->get($player,null);
//            $d = new Config("SKC-Rewrite/player-databases/".strtolower($username[0]).".txt", Config::YAML, array());
//            $player->sendMessage("Converting old PVP config...");
//            $c->set("pvp-kills",$d->get("kills"));
//            $c->set("pvp-deaths",$d->get("deaths"));
//            $c->set("pvp-KDR",($d->get("kills") / $d->get("deaths")));
//            $player->sendMessage("Your PVP config has been converted!");
//            unlink("SKC-Rewrite/player-databases/".strtolower($username[0])."/".strtolower($username).".txt");
//        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onInteract(PlayerInteractEvent $event){
        $blockID = $event->getBlock()->getID();
        $player = $event->getPlayer();
        if($blockID==63 or $blockID==68 or $blockID==323){
            $tile = $this->getTile(new Position($event->getBlock()->getX(),$event->getBlock()->getY(),$event->getBlock()->getZ(),$player->getLevel()));
            if($tile->getText(0)=="[Teleport]" and $tile->getText(1)=="to the PVP world!"){
                for($i=0;$i<36;$i++) $player->getInventory()->clear($i);
                $player->getInventory()->sendContents($player);
                for($i=0;$i<4;$i++) $player->getInventory()->setArmorItem($i,Item::get(0));
                $player->getInventory()->sendArmorContents($player);
                $player->teleport($this->getServer()->getLevel("world_pvp")->getSafeSpawn());
                $conf = $this->get($player,null)->getAll();
                for($a=0;$a<count($conf["pvp-kit"]["armor"]);$a++){
                    $player->getInventory()->setArmorItem($a,Item::get($conf["pvp-kit"]["armor"][$a]));
                }
                $player->getInventory()->sendArmorContents($player);
                for($i=0;$i<count($conf["pvp-kit"]["items"]);$i++){
                    $player->getInventory()->addItem(Item::get($conf["pvp-kit"]["items"][$i]["id"],0,$conf["pvp-kit"]["items"][$i]["count"]));
                }
                $player->getInventory()->sendContents($player);
            }
            if($tile->getText(0)=="[Teleport]" and $tile->getText(1)=="to the Spleef world!"){
                for($i=0;$i<36;$i++) $player->getInventory()->clear($i);
                for($i=0;$i<4;$i++) $player->getInventory()->setArmorItem($i,Item::get(0));
                $player->teleport($this->getServer()->getLevel("world_spleef")->getSafeSpawn());
            }
            if($tile->getText(0)=="[Teleport]" and $tile->getText(1)=="to the Infected world!"){
                //$player->teleport($this->server->getLevel("world_pvp")->getSafeSpawn());
                $player->sendMessage("This world doesn't work yet!");
            }
            if($tile->getText(0)=="[Teleport]" and $tile->getText(1)=="to the Parkour world!"){
                for($i=0;$i<36;$i++) $player->getInventory()->clear($i);
                for($i=0;$i<4;$i++) $player->getInventory()->setArmorItem($i,Item::get(0));
                $player->teleport($this->getServer()->getLevel("world_parkour")->getSafeSpawn());
            }


            //parkour


            if($tile->getText(0)=="[Parkour]" and $tile->getText(1)=="Get your" and $tile->getText(3)=="prefix!"){
                $this->set($player,"parkour-prefix",$tile->getText(2));
            }


            //pvp shop


            if($tile->getText(0)=="[PVP Shop]" and $tile->getText(1)=="Buy a" and $tile->getText(2)=="Golden Helmet" and $tile->getText(3)=="for 40 coins"){
                if(!in_array(314,$this->get($player,"pvp-items-purchased"))){
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][0] = 314;
                    $this->set($player,"pvp-kit",$kit);

                    $purchased = $this->get($player,"pvp-items-purchased");
                    array_push($purchased,314);
                    $this->set($player,"pvp-items-purchased",$purchased);

                    $this->coins($player,"take",40);
                    $player->sendMessage("You have bought and equipped a Golden Helmet!");
                }else{
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][0] = 314;
                    $this->set($player,"pvp-kit",$kit);
                    $player->sendMessage("You have equipped a Golden Helmet!");
                }
            }
            if($tile->getText(0)=="[PVP Shop]" and $tile->getText(1)=="Buy a" and $tile->getText(2)=="Golden Chestplate" and $tile->getText(3)=="for 75 coins"){
                if(!in_array(315,$this->get($player,"pvp-items-purchased"))){
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][1] = 315;
                    $this->set($player,"pvp-kit",$kit);

                    $purchased = $this->get($player,"pvp-items-purchased");
                    array_push($purchased,315);
                    $this->set($player,"pvp-items-purchased",$purchased);

                    $this->coins($player,"take",75);
                    $player->sendMessage("You have bought and equipped a Golden Chestplate!");
                }else{
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][1] = 315;
                    $this->set($player,"pvp-kit",$kit);
                    $player->sendMessage("You have equipped a Golden Chestplate!");
                }
            }
            if($tile->getText(0)=="[PVP Shop]" and $tile->getText(1)=="Buy" and $tile->getText(2)=="Golden Leggings" and $tile->getText(3)=="for 65 coins"){
                if(!in_array(316,$this->get($player,"pvp-items-purchased"))){
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][2] = 316;
                    $this->set($player,"pvp-kit",$kit);

                    $purchased = $this->get($player,"pvp-items-purchased");
                    array_push($purchased,316);
                    $this->set($player,"pvp-items-purchased",$purchased);

                    $this->coins($player,"take",75);
                    $player->sendMessage("You have bought and equipped a Golden Chestplate!");
                }else{
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][2] = 316;
                    $this->set($player,"pvp-kit",$kit);
                    $player->sendMessage("You have equipped Golden Leggings!");
                }
            }
            if($tile->getText(0)=="[PVP Shop]" and $tile->getText(1)=="Buy" and $tile->getText(2)=="Golden Boots" and $tile->getText(3)=="for 20 coins"){
                if(!in_array(317,$this->get($player,"pvp-items-purchased"))){
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][3] = 317;
                    $this->set($player,"pvp-kit",$kit);

                    $purchased = $this->get($player,"pvp-items-purchased");
                    array_push($purchased,317);
                    $this->set($player,"pvp-items-purchased",$purchased);

                    $this->coins($player,"take",75);
                    $player->sendMessage("You have bought and equipped Golden Boots!");
                }else{
                    $kit = $this->get($player,"pvp-kit");
                    $kit["armor"][3] = 317;
                    $this->set($player,"pvp-kit",$kit);
                    $player->sendMessage("You have equipped Golden Boots!");
                }
            }
        }
    }

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if($sender instanceof Player){
			$level = $sender->getLevel()->getName();
			$cmd = $command->getName();
    		if($cmd=="stats"){
        		if($level=="world"){
        			if(isset($args[0])){
        				if($args[0]=="pvp" or $args[0]=="world_pvp"){
        					$sender->sendMessage("[PVP Stats]:\n- prefix: ".$this->get($sender,"pvp-prefix")."\n- kills: ".$this->get($sender,"pvp-kills")."\n- deaths: ".$this->get($sender,"pvp-deaths")."\n- kill/death ratio: ".$this->get($sender,"pvp-KDR"));
        				}else
        				if($args[0]=="spleef" or $args[0]=="world_spleef"){
        					$sender->sendMessage("[Spleef Stats]:\n- prefix: ".$this->get($sender,"spleef-prefix")."\n- wins: ".$this->get($sender,"spleef-wins")."\n- loses: ".$this->get($sender,"spleef-loses")."\n- win/lose ratio: ".$this->get($sender,"spleef-WLR"));
        				}else
        				if($args[0]=="parkour" or $args[0]=="world_parkour"){
        					$sender->sendMessage("[Parkour Stats]:\n- prefix: ".$this->get($sender,"parkour-prefix")."\n- maps finished: ".implode(", ",$this->get($sender,"parkour-maps-finished")));
        				}else{
        					$sender->sendMessage("Unknown world.\nUsage: /stats <pvp/spleef/parkour>");
        				}
        			}else{
                        $sender->sendMessage("Usage: /stats <pvp/spleef/parkour>");
                    }
        		}else
        		if($level=="world_pvp"){
        			$sender->sendMessage("[PVP Stats]:\n- prefix: ".$this->get($sender,"pvp-prefix")."\n- kills: ".$this->get($sender,"pvp-kills")."\n- deaths: ".$this->get($sender,"pvp-deaths")."\n- kill/death ratio: ".$this->get($sender,"pvp-KDR"));
        		}else
        		if($level=="world_spleef"){
        			$sender->sendMessage("[Spleef Stats]:\n- prefix: ".$this->get($sender,"spleef-prefix")."\n- wins: ".$this->get($sender,"spleef-wins")."\n- loses: ".$this->get($sender,"spleef-loses")."\n- win/lose ratio: ".$this->get($sender,"spleef-WLR"));
        		}else
        		if($level=="world_parkour"){
        			$sender->sendMessage("[Parkour Stats]:\n- prefix: ".$this->get($sender,"parkour-prefix")."\n- maps finished: ".implode(", ",$this->get($sender,"parkour-maps-finished")));
        		}else{
                    $sender->sendMessage("You are in a unknown world!");
                }
    		}else
    		if($cmd=="hub"){
    			$sender->teleport($this->getServer()->getLevel("world")->getSafeSpawn());
    			$sender->sendMessage("You have been teleported to hub.");
    		}else
            if($cmd=="pvp"){
                for($i=0;$i<36;$i++) $sender->getInventory()->clear($i);
                $sender->getInventory()->sendContents($sender);
                for($i=0;$i<4;$i++) $sender->getInventory()->setArmorItem($i,Item::get(0));
                $sender->getInventory()->sendArmorContents($sender);
                $sender->teleport($this->getServer()->getLevel("world_pvp")->getSafeSpawn());
                $conf = $this->get($sender,null)->getAll();
                for($a=0;$a<count($conf["pvp-kit"]["armor"]);$a++){
                    $sender->getInventory()->setArmorItem($a,Item::get($conf["pvp-kit"]["armor"][$a]));
                }
                $sender->getInventory()->sendArmorContents($sender);
                for($i=0;$i<count($conf["pvp-kit"]["items"]);$i++){
                    $sender->getInventory()->addItem(Item::get($conf["pvp-kit"]["items"][$i]["id"],0,$conf["pvp-kit"]["items"][$i]["count"]));
                }
                $sender->getInventory()->sendContents($sender);
            }
        }
        return true;
    }

//    public function getMsg(Player $player, $message){
//        $msg=null;
//        if($player->getLevel()->getName()=="world"){
//            if(in_array($player->getName(),$this->ranks->get("moderator")) or in_array($player->getName,$this->ranks->get("admin")) or in_array($player->getName,$this->ranks->get("developer")) or in_array($player->getName,$this->ranks->get("owner"))){
//                $msg = "[".$this->getPrefix($player)."]".$player->getName().": ".$message;
//            }else
//            if(strlen($message) > 1){
//                if(strlen($message) < 50){
//                    if((strpos($message,"fuck") !== false) and (strpos($message,"cunt") !== false) and (strpos($message,"penis") !== false) and (strpos($message,"vagina") !== false) and (strpos($message,"bitch") !== false) and (strpos($message,"dick") !== false) and (strpos($message,"asshole") !== false) and (strpos($message,"bastard") !== false) and (strpos($message,"bullshit") !== false)){
//                        if($message != $this->players[$player->getName]["msg"]){
//                            if((time() - $this->players[$player->getName()]["time"]) > 7){
//                                $msg = $player->getName().": ".$message;
//                            }else $player->sendMessage("Please wait ".(7 - (time() - $this->players[$player->getName()]["time"]))." seconds.");
//                        }else $player->sendMessage("You cannot send the same message!");$msg=false;
//                    }else $player->sendMessage("Your message contains a blacklisted word!");$msg=false;
//                }else $player->sendMessage("Your message is too long!");$msg=false;
//            }else $player->sendMessage("Your message is too short!");$msg=false;
//        }else
//        if($player->getLevel()->getName()=="world_parkour"){
//            if(in_array($player->getName,$this->ranks->get("moderator")) or in_array($player->getName,$this->ranks->get("admin")) or in_array($player->getName,$this->ranks->get("developer")) or in_array($player->getName,$this->ranks->get("owner"))){
//                $msg = "[".$this->getPrefix($player)."][".$this->get($player,"parkour-prefix")."]".$player->getName().": ".$message;
//            }else
//            if(strlen($message) > 1){
//                if(strlen($message) < 50){
//                    if((strpos($message,"fuck") !== false) and (strpos($message,"cunt") !== false) and (strpos($message,"penis") !== false) and (strpos($message,"vagina") !== false) and (strpos($message,"bitch") !== false) and (strpos($message,"dick") !== false) and (strpos($message,"asshole") !== false) and (strpos($message,"bastard") !== false) and (strpos($message,"bullshit") !== false)){
//                        if($message != $this->players[$player->getName]["msg"]){
//                            if((time() - $this->players[$player->getName()]["time"]) > 7){
//                                $msg = "[".$this->get($player,"parkour-prefix")."]".$player->getName().": ".$message;
//                            }else $player->sendMessage("Please wait ".(7 - (time() - $this->players[$player->getName()]["time"]))." seconds.");
//                        }else $player->sendMessage("You cannot send the same message!");$msg=false;
//                    }else $player->sendMessage("Your message contains a blacklisted word!");$msg=false;
//                }else $player->sendMessage("Your message is too long!");$msg=false;
//            }else $player->sendMessage("Your message is too short!");$msg=false;
//        }else
//        if($player->getLevel()->getName()=="world_pvp"){
//            if(in_array($player->getName,$this->ranks->get("moderator")) or in_array($player->getName,$this->ranks->get("admin")) or in_array($player->getName,$this->ranks->get("developer")) or in_array($player->getName,$this->ranks->get("owner"))){
//                $msg = "[".$this->getPrefix($player)."][".$this->get($player,"pvp-kills")."]".$player->getName().": ".$message;
//            }else
//            if(strlen($message) > 1){
//                if(strlen($message) < 50){
//                    if((strpos($message,"fuck") !== false) and (strpos($message,"cunt") !== false) and (strpos($message,"penis") !== false) and (strpos($message,"vagina") !== false) and (strpos($message,"bitch") !== false) and (strpos($message,"dick") !== false) and (strpos($message,"asshole") !== false) and (strpos($message,"bastard") !== false) and (strpos($message,"bullshit") !== false)){
//                        if($message != $this->players[$player->getName]["msg"]){
//                            if((time() - $this->players[$player->getName()]["time"]) > 7){
//                                $kills = $this->get($player,"pvp-kills");
//                                $tag=null;
//                                if($kills == 0 or $kills == null) $kills = 0;$tag="";
//                                if($kills >=25) $tag="[Fighter]";
//                                if($kills >=75) $tag="[Killer]";
//                                if($kills >=150) $tag="[Dangerous]";
//                                if($kills >=250) $tag="[Hard]";
//                                if($kills >=375) $tag="[Beast]";
//                                if($kills >=525) $tag="[Elite]";
//                                if($kills >=675) $tag="[Warrior]";
//                                if($kills >=870) $tag="[Knight]";
//                                if($kills >=1100) $tag="[Addict]";
//                                if($kills >=1350) $tag="[Unstoppable]";
//                                if($kills >=1625) $tag="[Pro]";
//                                if($kills >=1925) $tag="[Hardcore]";
//                                if($kills >=2250) $tag="[Master]";
//                                if($kills >=2600) $tag="[Legend]";
//                                if($kills >=2975) $tag="[God]";
//                                if($kills == 0) $kills="";
//                                else $kills="[".$kills."]";
//                                $msg = $tag.$kills.$player->getName().": ".$message;
//                            }else $player->sendMessage("Please wait ".(7 - (time() - $this->players[$player->getName()]["time"]))." seconds.");
//                        }else $player->sendMessage("You cannot send the same message!");$msg=false;
//                    }else $player->sendMessage("Your message contains a blacklisted word!");$msg=false;
//                }else $player->sendMessage("Your message is too long!");$msg=false;
//            }else $player->sendMessage("Your message is too short!");$msg=false;
//        }else
//        if($player->getLevel()->getName()=="world_spleef"){
//            if(in_array($player->getName,$this->ranks->get("moderator")) or in_array($player->getName,$this->ranks->get("admin")) or in_array($player->getName,$this->ranks->get("developer")) or in_array($player->getName,$this->ranks->get("owner"))){
//                $msg = "[".$this->getPrefix($player)."][".$this->get($player,"spleef-kills")."]".$player->getName().": ".$message;
//            }else
//            if(strlen($message) > 1){
//                if(strlen($message) < 50){
//                    if((strpos($message,"fuck") !== false) and (strpos($message,"cunt") !== false) and (strpos($message,"penis") !== false) and (strpos($message,"vagina") !== false) and (strpos($message,"bitch") !== false) and (strpos($message,"dick") !== false) and (strpos($message,"asshole") !== false) and (strpos($message,"bastard") !== false) and (strpos($message,"bullshit") !== false)){
//                        if($message != $this->players[$player->getName]["msg"]){
//                            if((time() - $this->players[$player->getName()]["time"]) > 7){
//                                $msg = "[".$this->get($player,"spleef-wins")."]".$player->getName().": ".$message;
//                            }else $player->sendMessage("Please wait ".(7 - (time() - $this->players[$player->getName()]["time"]))." seconds.");
//                        }else $player->sendMessage("You cannot send the same message!");$msg=false;
//                    }else $player->sendMessage("Your message contains a blacklisted word!");$msg=false;
//                }else $player->sendMessage("Your message is too long!");$msg=false;
//            }else $player->sendMessage("Your message is too short!");$msg=false;
//        }else{
//            $msg = $player->getName.": ".$message;
//        }
//
//        if($msg !== false) $this->players[$player->getName()]=array("time"=>time(),"msg"=>$message);
//
//        return $msg;
//    }
    
    //how do I use these functions in other plugins?

    public function getRank(Player $player){
        $rank=null;
        foreach($this->ranks->getAll() as $r=>$b){
            if(in_array($player->getName(),$b)){
                $rank=$r;
            }
        }
        return $rank;
    }

    public function getPrefix(Player $player){
        return ucfirst(strtolower($this->getRank($player)));
    }

    public function getHealHalfHearts(Player $player){
        $hh = 2;
        if($this->getRank($player)=="donater"){
            $hh = 4;
        }else
        if($this->getRank($player)=="donater+"){
            $hh = 6;
        }else
        if($this->getRank($player)=="vip"){
            $hh = 8;
        }else
        if($this->getRank($player)=="vip+"){
            $hh = 10;
        }else
        if($this->getRank($player)=="moderator"){
            $hh = 5;
        }else
        if($this->getRank($player)=="admin"){
            $hh = 8;
        }else
        if($this->getRank($player)=="owner"){
            $hh = 20;
        }
        return $hh;
    }

	public function get(Player $player, $stat){
		$conf = new Config("player-databases/players/".strtolower($player->getName()).".txt", Config::YAML, array());
        if($stat===null) return $conf;
        else return $conf->get($stat);
	}

    public function coins(Player $plyr, $action, $value){
        if($action=="take"){
            if($this->get($plyr,"coins") >= $value){
                $this->set($plyr, "coins", ($this->get($plyr,"coins") - $value));
                $plyr->sendMessage($value." coins have been taken from your coins.\nYou now have ".$this->get($plyr,"coins")." coins.");
            }else $plyr->sendMessage("You don't have enough coins!");
        }else
        if($action=="add"){
            $this->set($plyr, "coins", ($this->get($plyr,"coins") + $value));
            $plyr->sendMessage($value." coins have been added to your coins.\nYou now have ".$this->get($plyr,"coins")." coins.");
        }
    }

    public function set(Player $plyr, $stat, $value){
        $conf = new Config("player-databases/players/".strtolower($plyr->getName()).".txt", Config::YAML, array());
        $conf->set($stat,$value);
        $conf->save();
    }

//	public function createConfig(Player $player){
//		$conf = new Config("player-databases/players/".strtolower($player->getName()).".txt", Config::YAML, array("prefix"=>null,"rank"=>"default","spleef-wins"=>0,"spleef-loses"=>0,"spleef-WLR"=>0,"spleef-items-purchased"=>array(),"spleef-item"=>284,"spleef-prefix"=>null,"pvp-kills"=>0,"pvp-deaths"=>0,"pvp-KDR"=>0,"pvp-prefix"=>0,"pvp-kit"=>array("armor"=>array("0"=>298,"1"=>299,"2"=>300,"3"=>301),"items"=>array("0"=>array("id"=>272,"count"=>3),"1"=>array("id"=>360,"count"=>32))),"pvp-kills-total"=>0,"pvp-items-purchased"=>array(),"parkour-prefix"=>null,"parkour-maps-finished"=>array(),"parkour-maps-purchased"=>array(),"coins"=>100,"reports"=>0,"counter"=>0,"kicks"=>0,"group-in"=>null,"group-leader"=>false,"group"=>null,"friends"=>array(),"friend-requests"=>array(),"friend-pending"=>array(),"infected-wins"=>0,"infected-loses"=>0,"infected-WLR"=>0,"infected-kit"=>array("armor"=>array("0"=>298,"1"=>299,"2"=>300,"3"=>301),"items"=>array("0"=>array("id"=>272,"count"=>3),"1"=>array("id"=>360,"count"=>32))),"infected-items-purchased"=>array()));
//		return $conf;
//	}

    public function getTile($poss, $forceArray = false){
        if($poss instanceof Position)
            $poss = array($poss);
        foreach(Tile::getAll() as $t){
            foreach($poss as $pos){
                if($t->x === $pos->x and $t->y === $pos->y and $t->z === $pos->z and $t->level->getName() === $pos->level->getName())
                    $ret[$pos->x.":".$pos->y.":".$pos->z."@".$pos->level->getName()] = $t;
                if(count($poss) === count($ret))
                    break;
            }
        }
        if(count($poss) === 1 and !$forceArray)
            return array_rand($poss);
        return $ret;
    }
}
