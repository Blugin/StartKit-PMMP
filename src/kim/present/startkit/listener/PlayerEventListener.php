<?php

declare(strict_types=1);

namespace kim\present\startkit\listener;

use kim\present\startkit\inventory\StartKitInventory;
use kim\present\startkit\StartKit;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class PlayerEventListener implements Listener{

	/** @var StartKit */
	private $owner = null;

	public function __construct(StartKit $owner){
		$this->owner = $owner;
	}

	/** @param PlayerJoinEvent $event */
	public function onPlayerJoinEvent(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		if(!$this->owner->isSupplied($playerName = $player->getName())){
			$this->owner->setSupplied($playerName, true);
			$player->getInventory()->addItem(...StartKitInventory::getInstance()->getContents());
		}
	}
}