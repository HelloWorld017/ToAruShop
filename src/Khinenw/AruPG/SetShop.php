<?php

namespace Khinenw\AruPG;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SetShop implements Shop{

	private $jobId;
	private $cost;
	private $desc;

	public function __construct($jobId, $cost, $desc){
		$this->cost = $cost;
		$this->jobId = $jobId;
		$this->desc = $desc;
	}

	public function canBuy(Player $buyer){
		if(!$buyer->hasPermission("arushop.buy.set")) return "NO_PERMISSION";
		if(EconomyAPI::getInstance()->myMoney($buyer) < $this->cost) return "INSUFFICIENT_MONEY";
		if(!ToAruPG::getInstance()->isValidPlayer($buyer)) return "INVALID_PLAYER";
		return true;
	}

	public function buy(Player $buyer){
		$rpg = ToAruPG::getInstance()->getRPGPlayerByName($buyer->getName());
		$job = JobManager::getJob($this->jobId);
		$rpg->changeJob($job);

		foreach($job->getSkills() as $skill){
			if(!$skill->canBeAcquired($rpg)){
				$buyer->sendMessage(TextFormat::RED.ToAruPG::getTranslation("SKILL_COULD_NOT_ACQUIRE"));
				continue;
			}

			$rpg->acquireSkill($skill);
		}

		EconomyAPI::getInstance()->reduceMoney($buyer, $this->cost, true, "To Aru Shop");
		$buyer->sendMessage(TextFormat::AQUA.ToAruPG::getTranslation("BOUGHT"));
	}

	public function getDescription(){
		return $this->desc;
	}

}