<?php
 
namespace Khinenw\AruPG;

use Khinenw\XcelUpdater\UpdatePlugin;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ToAruShop extends UpdatePlugin implements Listener{

	/**
	 * @var $shops Shop[]
	 */
	private $shops;

	private $doubleTap, $itemPlaceList;

	public function onEnable(){
		@mkdir($this->getDataFolder());

		if(!is_file($this->getDataFolder()."shops.json")){
			file_put_contents($this->getDataFolder()."shops.json", json_encode([]));
		}

		$shops = json_decode(file_get_contents($this->getDataFolder()."shops.json"), true);

		$translations = yaml_parse(stream_get_contents($this->getResource("translation.yml")));

		foreach($translations as $name => $data){
			ToAruPG::addTranslation($name, $data);
		}

		foreach($shops as $tag => $meta){
			switch($meta["type"]){
				case "SET":
					$this->shops[$tag] = new SetShop($meta["meta"], $meta["cost"], $meta["desc"]);
					break;

				case "JOB":
					$this->shops[$tag] = new JobShop($meta["meta"], $meta["cost"], $meta["desc"]);
					break;

				case "SKILL":
					$this->shops[$tag] = new SkillShop($meta["meta"], $meta["cost"], $meta["desc"]);
					break;
			}
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->doubleTap = [];
		$this->itemPlaceList = [];
	}

	public function onSignChange(SignChangeEvent $event){
		$text = $event->getLines();
		$prefix = strtoupper($text[0]);
		if($prefix !== "[ARUSHOP]") return;

		if(!$event->getPlayer()->hasPermission("arushop.create")){
			$event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation("NO_PERMISSION"));
			return;
		}

		$cost = $text[2];
		$meta = $text[3];

		$tag = $event->getBlock()->getX().";".$event->getBlock()->getY().";".$event->getBlock()->getZ().";".$event->getBlock()->getLevel()->getFolderName();

		if(!is_numeric($cost) || !is_numeric($meta)){
			$event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation("WRONG_SHOP_META"));
			return;
		}

		switch(strtoupper($text[1])){
			case "JOB":
				if(JobManager::getJob($meta) === null){
					$event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation("WRONG_SHOP_META"));
					return;
				}
				$this->shops[$tag] = new JobShop($meta, $cost, ToAruPG::getTranslation("TAP_ONE_MORE"));
				$meta = ToAruPG::getTranslation(JobManager::getJob($meta)->getName());
				$name = ToAruPG::getTranslation("JOB_SHOP");
				break;

			case "SKILL":
				if(SkillManager::getSkill($meta) === null){
					$event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation("WRONG_SHOP_META"));
					return;
				}
				$this->shops[$tag] = new SkillShop($meta, $cost, ToAruPG::getTranslation("TAP_ONE_MORE"));
				$meta = ToAruPG::getTranslation(SkillManager::getSkill($meta)->getName());
				$name = ToAruPG::getTranslation("SKILL_SHOP");
				break;

			case "SET":
				if(JobManager::getJob($meta) === null){
					$event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation("WRONG_SHOP_META"));
					return;
				}
				$this->shops[$tag] = new SetShop($meta, $cost, ToAruPG::getTranslation("TAP_ONE_MORE"));
				$meta = ToAruPG::getTranslation(JobManager::getJob($meta)->getName());
				$name = ToAruPG::getTranslation("SET_SHOP");
				break;

			default: $event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation("WRONG_SHOP_META")); return;
		}
		$this->saveShops();

		$event->setLine(0, $name);
		$event->setLine(1, TextFormat::AQUA.$meta);
		$event->setLine(2, $cost.EconomyAPI::getInstance()->getMonetaryUnit());
		$event->setLine(3, "");
		$event->getPlayer()->sendMessage(TextFormat::AQUA.ToAruPG::getTranslation("SHOP_CREATED"));
	}

	public function onBlockBreak(BlockBreakEvent $event){
		$tag = $event->getBlock()->getX().";".$event->getBlock()->getY().";".$event->getBlock()->getZ().";".$event->getBlock()->getLevel()->getFolderName();
		if(isset($this->shops[$tag])){
			if(!$event->getPlayer()->hasPermission("arushop.destroy")){
				$event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation("NO_PERMISSION"));
				$event->setCancelled();
			}else{
				unset($this->shops[$tag]);
				$event->getPlayer()->sendMessage(TextFormat::AQUA.ToAruPG::getTranslation("SHOP_DESTROYED"));
				$this->saveShops();
			}
		}
	}

	public function saveShops(){
		$shops = [];
		foreach($this->shops as $tag => $shop){
			$shops[$tag] = $shop->getSaveData();
		}

		file_put_contents($this->getDataFolder()."shops.json", json_encode($shops));
	}

	public function onBlockPlace(BlockPlaceEvent $event){
		if(isset($this->itemPlaceList[$event->getPlayer()->getName()]) && $this->itemPlaceList[$event->getPlayer()->getName()]){
			$event->setCancelled(true);
			unset($this->itemPlaceList[$event->getPlayer()->getName()]);
		}
	}

	public function setDoubleTap(Player $player, $tag){
		$this->doubleTap[$player->getName()] = array(
			"id" => $tag,
			"time" => microtime(true)
		);
		$player->sendMessage($this->shops[$tag]->getDescription());
	}

	public function onPlayerInteract(PlayerInteractEvent $event){
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}

		$tag = $event->getBlock()->getX().";".$event->getBlock()->getY().";".$event->getBlock()->getZ().";".$event->getBlock()->getLevel()->getFolderName();

		if(!isset($this->shops[$tag])){
			return;
		}

		if(!isset($this->doubleTap[$event->getPlayer()->getName()])){
			$this->setDoubleTap($event->getPlayer(), $tag);
			return;
		}

		if($this->doubleTap[$event->getPlayer()->getName()]["id"] !== $tag){
			$this->setDoubleTap($event->getPlayer(), $tag);
			return;
		}

		if(($this->doubleTap[$event->getPlayer()->getName()]["time"] - microtime(true) >= 1.5)){
			$this->setDoubleTap($event->getPlayer(), $tag);
			return;
		}

		unset($this->doubleTap[$event->getPlayer()->getName()]);

		$returnVal = $this->shops[$tag]->canBuy($event->getPlayer());

		if($returnVal !== true){
			$event->getPlayer()->sendMessage(TextFormat::RED.ToAruPG::getTranslation($returnVal));
			return;
		}

		$this->shops[$tag]->buy($event->getPlayer());

		if($event->getItem()->canBePlaced()){
			$this->itemPlaceList[$event->getPlayer()->getName()] = true;
		}

		$event->setCancelled(true);
	}

	public function compVersion($pluginVersion, $repoVersion){
		return $pluginVersion !== $repoVersion;
	}

	public function getPluginYamlURL(){
		return "https://raw.githubusercontent.com/HelloWorld017/ToAruShop/master/plugin.yml";
	}
}
