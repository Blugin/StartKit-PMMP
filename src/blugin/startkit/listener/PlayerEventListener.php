<?php

declare(strict_types=1);

namespace blugin\startkit\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use blugin\startkit\StartKit;
use blugin\startkit\inventory\StartKitInventory;

class PlayerEventListener implements Listener{

    /** @var StartKit */
    private $owner = null;

    public function __construct(){
        $this->owner = StartKit::getInstance();
    }

    /** @param PlayerJoinEvent $event */
    public function onPlayerJoinEvent(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        if (!$this->owner->isSupplied($playerName = $player->getName())) {
            $this->owner->setSupplied($playerName, true);
            $player->getInventory()->addItem(...StartKitInventory::getInstance()->getContents());
        }
    }
}