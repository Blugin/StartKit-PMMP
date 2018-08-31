<?php

/*
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0.0
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\startkit\listener;

use kim\present\startkit\inventory\StartKitInventory;
use kim\present\startkit\StartKit;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class PlayerEventListener implements Listener{
	/** @var StartKit */
	private $plugin = null;

	/**
	 * PlayerEventListener constructor.
	 *
	 * @param StartKit $plugin
	 */
	public function __construct(StartKit $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @priority MONITOR
	 *
	 * @param PlayerJoinEvent $event
	 */
	public function onPlayerJoinEvent(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();

		//Support for older data conversion
		try{
			$namedtag = $player->getServer()->getOfflinePlayerData($player->getName());
			if($namedtag->hasTag(StartKit::TAG_PLUGIN)){
				$this->plugin->setSupplied($player, true);
			}
		}catch(\Exception $e){
		}

		if(!$this->plugin->isSupplied($player)){
			$this->plugin->setSupplied($player, true);
			$player->getInventory()->addItem(...StartKitInventory::getInstance()->getContents());
		}
	}
}