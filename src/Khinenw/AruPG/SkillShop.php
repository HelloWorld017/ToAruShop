<?php

namespace Khinenw\AruPG;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SkillShop implements Shop{

	private $skillId;
	private $cost;
	private $desc;

	public function __construct($skillId, $cost, $desc){
		$this->cost = $cost;
		$this->skillId = $skillId;
		$this->desc = $desc;
	}

	public function canBuy(Player $buyer){
		if(!$buyer->hasPermission("arushop.buy.skill")) return "NO_PERMISSION";
		if(EconomyAPI::getInstance()->myMoney($buyer) < $this->cost) return "INSUFFICIENT_MONEY";
		if(!ToAruPG::getInstance()->isValidPlayer($buyer)) return "INVALID_PLAYER";

		$rpgPlayer = ToAruPG::getInstance()->getRPGPlayerByName($buyer);

		if(!SkillManager::getSkill($this->skillId)->canBeAcquired($rpgPlayer)) return "CANNOT_BE_ACQUIRED";

		return true;
	}

	public function buy(Player $buyer){
		$rpg = ToAruPG::getInstance()->getRPGPlayerByName($buyer);
		$skill = SkillManager::getSkill($this->skillId);
		$rpg->acquireSkill($skill);
		EconomyAPI::getInstance()->reduceMoney($buyer, $this->cost, true, "To Aru Shop");
		$buyer->sendMessage(TextFormat::AQUA.ToAruPG::getTranslation("BOUGHT"));
	}

	public function getDescription(){
		return $this->desc;
	}

	public function getSaveData(){
		return [
			"type" => "SKILL",
			"meta" => $this->skillId,
			"cost" => $this->cost,
			"desc" => $this->desc
		];
	}

}