<?php

namespace presentkim\startkit\inventory;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\inventory\{
  BaseInventory, CustomInventory
};
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\{
  CompoundTag, IntTag, ListTag, StringTag
};
use pocketmine\network\mcpe\protocol\{
  types\WindowTypes, UpdateBlockPacket, ContainerOpenPacket, BlockEntityDataPacket
};
use pocketmine\tile\Spawnable;
use presentkim\startkit\util\Translation;

class StartKitInventory extends CustomInventory{

    /** @var StartKitInventory */
    private static $instance = null;

    /** @return StartKitInventory */
    public static function getInstance() : self{
        if (self::$instance === null) {
            self::$instance = new StartKitInventory();
        }
        return self::$instance;
    }

    /** CompoundTag */
    private $nbt;

    /** Vector3[] */
    private $vectors = [];

    private function __construct(){
        parent::__construct(new Vector3(0, 0, 0), [], 27, null);

        $this->nbt = new CompoundTag('', [
          new StringTag('id', 'Chest'),
          new IntTag('x', 0),
          new IntTag('y', 0),
          new IntTag('z', 0),
          new StringTag('CustomName', ''),
        ]);
    }

    /** @param Player $who */
    public function onOpen(Player $who) : void{
        BaseInventory::onOpen($who);

        $this->vectors[$key = $who->getLowerCaseName()] = $who->subtract(0, 3, 0)->floor();
        if ($this->vectors[$key]->y < 0) {
            $this->vectors[$key]->y = 0;
        }

        $pk = new UpdateBlockPacket();
        $pk->blockId = Block::CHEST;
        $pk->blockData = 0;
        $pk->x = $this->vectors[$key]->x;
        $pk->y = $this->vectors[$key]->y;
        $pk->z = $this->vectors[$key]->z;
        $who->sendDataPacket($pk);


        $this->nbt->setInt('x', $this->vectors[$key]->x);
        $this->nbt->setInt('y', $this->vectors[$key]->y);
        $this->nbt->setInt('z', $this->vectors[$key]->z);
        $this->nbt->setString('CustomName', Translation::translate('start-kit'));

        $pk = new BlockEntityDataPacket();
        $pk->x = $this->vectors[$key]->x;
        $pk->y = $this->vectors[$key]->y;
        $pk->z = $this->vectors[$key]->z;
        $pk->namedtag = (new NetworkLittleEndianNBTStream())->write($this->nbt);
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
        $pk->blockId = $block->getId();
        $pk->blockData = $block->getDamage();
        $who->sendDataPacket($pk);

        $tile = $who->getLevel()->getTile($this->vectors[$key]);
        if ($tile instanceof Spawnable) {
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
     * @param string $tagName
     *
     * @return ListTag
     */
    public function nbtSerialize(string $tagName = 'Kit') : ListTag{
        $tag = new ListTag($tagName, [], NBT::TAG_Compound);
        for ($slot = 0; $slot < 27; ++$slot) {
            $item = $this->getItem($slot);
            if (!$item->isNull()) {
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
        foreach ($tag as $i => $itemTag) {
            $inventory->setItem($itemTag->getByte("Slot"), Item::nbtDeserialize($itemTag));
        }
        return $inventory;
    }
}