<?php

namespace Khinenw\AruPG;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class JobShop implements Shop{

	private $jobId;
	private $cost;
	private $desc;

	public function __construct($jobId, $cost, $desc){
		$this->cost = $cost;
		$this->jobId = $jobId;
		$this->desc = $desc;
	}

	public function canBuy(Player $buyer){
		if(!$buyer->hasPermission("arushop.buy.job")) return "NO_PERMISSION";
		if(EconomyAPI::getInstance()->myMoney($buyer) < $this->cost) return "INSUFFICIENT_MONEY";
		if(!ToAruPG::getInstance()->isValidPlayer($buyer)) return "INVALID_PLAYER";

		return true;
	}

	public function buy(Player $buyer){
		ToAruPG::getInstance()->getRPGPlayerByName($buyer)->changeJob(JobManager::getJob($this->jobId));
		EconomyAPI::getInstance()->reduceMoney($buyer, $this->cost, true, "To Aru Shop");
		$buyer->sendMessage(TextFormat::AQUA.ToAruPG::getTranslation("BOUGHT"));
	}

	public function getDescription(){
		return $this->desc;
	}

	public function getSaveData(){
		return [
			"type" => "JOB",
			"meta" => $this->jobId,
			"cost" => $this->cost,
			"desc" => $this->desc
		];
	}
}
