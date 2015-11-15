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

		if(($rpg = ToAruPG::getInstance()->getRPGPlayerByName($buyer->getName())) === null) return "INVALID_PLAYER";
		if($rpg->canChangeJob(JobManager::getJob($this->jobId))) return "JOB_COULD_NOT_ACQUIRE";

		return true;
	}

	public function buy(Player $buyer){
		$rpg = ToAruPG::getInstance()->getRPGPlayerByName($buyer);
		$job = JobManager::getJob($this->jobId);
        $rpg->changeJob($job);

		foreach($job->getSkills() as $skill){
			$skill = SkillManager::getSkill($skill);
			if(!$skill->canBeAcquired($rpg)){
				$buyer->sendMessage(TextFormat::RED.ToAruPG::getTranslation("SKILL_COULD_NOT_ACQUIRE", ToAruPG::getTranslation($skill->getName())));
				continue;
			}

			if($rpg->hasSkill($skill->getId())){
				$buyer->sendMessage(TextFormat::RED.ToAruPG::getTranslation("ALREADY_HAS_SKILL", ToAruPG::getTranslation($skill->getName())));
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

	public function getSaveData(){
		return [
			"type" => "SET",
			"meta" => $this->jobId,
			"cost" => $this->cost,
			"desc" => $this->desc
		];
	}

}