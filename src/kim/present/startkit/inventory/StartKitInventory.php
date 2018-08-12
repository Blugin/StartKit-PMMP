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

namespace kim\present\startkit\inventory;

use kim\present\startkit\StartKit;
use pocketmine\block\{
	Block, BlockFactory
};
use pocketmine\inventory\{
	BaseInventory, CustomInventory
};
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\{
	NBT, NetworkLittleEndianNBTStream
};
use pocketmine\nbt\tag\{
	CompoundTag, IntTag, ListTag, StringTag
};
use pocketmine\network\mcpe\protocol\{
	BlockEntityDataPacket, ContainerOpenPacket, types\WindowTypes, UpdateBlockPacket
};
use pocketmine\Player;
use pocketmine\tile\{
	Chest, Spawnable, Tile
};

class StartKitInventory extends CustomInventory{
	/** @var StartKitInventory */
	private static $instance = null;

	/** @return StartKitInventory */
	public static function getInstance() : self{
		if(self::$instance === null){
			self::$instance = new StartKitInventory();
		}
		return self::$instance;
	}

	/** Vector3[] */
	private $vectors = [];

	private function __construct(){
		parent::__construct(new Vector3(), [], $this->getDefaultSize(), null);
	}

	/** @param Player $who */
	public function onOpen(Player $who) : void{
		BaseInventory::onOpen($who);

		$this->vectors[$key = $who->getLowerCaseName()] = $who->subtract(0, 3, 0)->floor();
		if($this->vectors[$key]->y < 0){
			$this->vectors[$key]->y = 0;
		}

		$pk = new UpdateBlockPacket();
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->blockRuntimeId = BlockFactory::toStaticRuntimeId(Block::CHEST);
		$pk->flags = UpdateBlockPacket::FLAG_NONE;
		$who->sendDataPacket($pk);


		$pk = new BlockEntityDataPacket();
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->namedtag = (new NetworkLittleEndianNBTStream())->write(new CompoundTag("", [
			new StringTag(Tile::TAG_ID, Tile::CHEST),
			new StringTag(Chest::TAG_CUSTOM_NAME, StartKit::getInstance()->getLanguage()->translate("startkit.name")),
			new IntTag(Tile::TAG_X, $this->vectors[$key]->x),
			new IntTag(Tile::TAG_Y, $this->vectors[$key]->y),
			new IntTag(Tile::TAG_Z, $this->vectors[$key]->z)
		]));
		$who->sendDataPacket($pk);


		$pk = new ContainerOpenPacket();
		$pk->type = WindowTypes::CONTAINER;
		$pk->entityUniqueId = -1;
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->windowId = $who->getWindowId($this);
		$who->sendDataPacket($pk);

		$this->sendContents($who);
	}

	public function onClose(Player $who) : void{
		BaseInventory::onClose($who);

		$block = $who->getLevel()->getBlock($this->vectors[$key = $who->getLowerCaseName()]);

		$pk = new UpdateBlockPacket();
		$pk->x = $this->vectors[$key]->x;
		$pk->y = $this->vectors[$key]->y;
		$pk->z = $this->vectors[$key]->z;
		$pk->blockRuntimeId = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage());
		$pk->flags = UpdateBlockPacket::FLAG_NONE;
		$who->sendDataPacket($pk);

		$tile = $who->getLevel()->getTile($this->vectors[$key]);
		if($tile instanceof Spawnable){
			$who->sendDataPacket($tile->createSpawnPacket());
		}
		unset($this->vectors[$key]);
	}

	/** @return string */
	public function getName() : string{
		return "StartKitInventory";
	}

	/** @return int */
	public function getDefaultSize() : int{
		return 27;
	}

	/** @return int */
	public function getNetworkType() : int{
		return WindowTypes::CONTAINER;
	}

	/**
	 * @param string $tagName = "Inventory"
	 *
	 * @return ListTag
	 */
	public function nbtSerialize(string $tagName = "Inventory") : ListTag{
		$tag = new ListTag($tagName, [], NBT::TAG_Compound);
		for($slot = 0; $slot < 27; ++$slot){
			$item = $this->getItem($slot);
			if(!$item->isNull()){
				$tag->push($item->nbtSerialize($slot));
			}
		}
		return $tag;
	}

	/**
	 * @param ListTag $tag
	 *
	 * @return StartKitInventory
	 */
	public static function nbtDeserialize(ListTag $tag) : StartKitInventory{
		$inventory = new StartKitInventory();
		/** @var CompoundTag $itemTag */
		foreach($tag as $i => $itemTag){
			$inventory->setItem($itemTag->getByte("Slot"), Item::nbtDeserialize($itemTag));
		}
		return $inventory;
	}
}