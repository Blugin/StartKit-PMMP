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
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://opensource.org/licenses/MIT MIT License
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
	CustomInventory
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
	BlockEntityDataPacket, types\WindowTypes, UpdateBlockPacket
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
			new StartKitInventory();
		}
		return self::$instance;
	}

	/** @var Vector3[] */
	private $holders = [];

	/**
	 * StartKitInventory constructor.
	 */
	private function __construct(){
		parent::__construct(new Vector3(), [], $this->getDefaultSize(), null);

		self::$instance = $this;
	}

	/**
	 * @param Player $who
	 */
	public function onOpen(Player $who) : void{
		$this->holders[$key = $who->getLowerCaseName()] = $who->subtract(0, 3, 0)->floor();
		if($this->holders[$key]->y < 0){
			$this->holders[$key]->y = 0;
		}
		$this->holder = $this->holders[$key];

		$pk = new UpdateBlockPacket();
		$pk->x = $this->holder->x;
		$pk->y = $this->holder->y;
		$pk->z = $this->holder->z;
		$pk->blockRuntimeId = BlockFactory::toStaticRuntimeId(Block::CHEST);
		$pk->flags = UpdateBlockPacket::FLAG_NONE;
		$who->sendDataPacket($pk);


		$pk = new BlockEntityDataPacket();
		$pk->x = $this->holder->x;
		$pk->y = $this->holder->y;
		$pk->z = $this->holder->z;
		$pk->namedtag = (new NetworkLittleEndianNBTStream())->write(new CompoundTag("", [
			new StringTag(Tile::TAG_ID, Tile::CHEST),
			new StringTag(Chest::TAG_CUSTOM_NAME, StartKit::getInstance()->getLanguage()->translate("startkit.name")),
			new IntTag(Tile::TAG_X, $this->holder->x),
			new IntTag(Tile::TAG_Y, $this->holder->y),
			new IntTag(Tile::TAG_Z, $this->holder->z)
		]));
		$who->sendDataPacket($pk);

		parent::onOpen($who);
	}

	/**
	 * @param Player $who
	 */
	public function onClose(Player $who) : void{
		$block = $who->getLevel()->getBlock($this->holders[$key = $who->getLowerCaseName()]);

		$pk = new UpdateBlockPacket();
		$pk->x = $this->holders[$key]->x;
		$pk->y = $this->holders[$key]->y;
		$pk->z = $this->holders[$key]->z;
		$pk->blockRuntimeId = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage());
		$pk->flags = UpdateBlockPacket::FLAG_NONE;
		$who->sendDataPacket($pk);

		$tile = $who->getLevel()->getTile($this->holders[$key]);
		if($tile instanceof Spawnable){
			$who->sendDataPacket($tile->createSpawnPacket());
		}
		unset($this->holders[$key]);

		parent::onClose($who);
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "StartKitInventory";
	}

	/**
	 * @return int
	 */
	public function getDefaultSize() : int{
		return 27;
	}

	/**
	 * @return int
	 */
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