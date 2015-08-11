<?php

namespace Khinenw\AruPG;

use pocketmine\Player;

interface Shop{
	public function canBuy(Player $buyer);

	public function buy(Player $buyer);

	public function getDescription();
	public function getSaveData();
}
