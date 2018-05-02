<?php

namespace blugin\startkit\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use blugin\startkit\StartKit as Plugin;
use blugin\startkit\inventory\StartKitInventory;

class PlayerEventListener implements Listener{

    /** @var Plugin */
    private $owner = null;

    public function __construct(){
        $this->owner = Plugin::getInstance();
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