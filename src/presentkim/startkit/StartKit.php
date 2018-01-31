<?php

namespace presentkim\startkit;

use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\nbt\{
  NBT, BigEndianNBTStream
};
use pocketmine\nbt\tag\{
  CompoundTag, ListTag, StringTag
};
use presentkim\startkit\command\PoolCommand;
use presentkim\startkit\command\subcommands\{
  OpenSubCommand, ResetSubCommand, LangSubCommand, ReloadSubCommand, SaveSubCommand
};
use presentkim\startkit\inventory\StartKitInventory;
use presentkim\startkit\listener\PlayerEventListener;
use presentkim\startkit\util\{
  Translation, Utils
};

class StartKit extends PluginBase{

    /** @var self */
    private static $instance = null;

    /** @var string */
    public static $prefix = '';

    /** @return self */
    public static function getInstance() : self{
        return self::$instance;
    }

    /** @var PoolCommand */
    private $command;

    /** @var String[] */
    private $supplieds = [];

    public function onLoad() : void{
        if (self::$instance === null) {
            self::$instance = $this;
            Translation::loadFromResource($this->getResource('lang/eng.yml'), true);
        }
    }

    public function onEnable() : void{
        $this->load();
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener(), $this);
    }

    public function onDisable() : void{
        $this->save();
    }

    public function load() : void{
        if (!file_exists($dataFolder = $this->getDataFolder())) {
            mkdir($dataFolder, 0777, true);
        }

        $langfilename = $dataFolder . 'lang.yml';
        if (!file_exists($langfilename)) {
            $resource = $this->getResource('lang/eng.yml');
            fwrite($fp = fopen("{$dataFolder}lang.yml", "wb"), $contents = stream_get_contents($resource));
            fclose($fp);
            Translation::loadFromContents($contents);
        } else {
            Translation::load($langfilename);
        }

        self::$prefix = Translation::translate('prefix');
        $this->reloadCommand();

        if (file_exists($file = "{$dataFolder}config.dat")) {
            try{
                $nbtStream = new BigEndianNBTStream();
                $nbtStream->readCompressed(file_get_contents($file));
                $namedTag = $nbtStream->getData();

                $this->supplieds = $namedTag->getListTag('SuppliedList')->getAllValues();

                $inventory = StartKitInventory::getInstance();
                foreach ($namedTag->getListTag('Kit') as $key => $tag) {
                    $inventory->setItem($tag->getByte('Slot'), Item::nbtDeserialize($tag));
                }
            } catch (\Throwable $e){
                rename($file, "{$file}.bak");
                $this->getLogger()->warning('Error occurred loading config.dat');
            }
        }
    }

    public function reloadCommand() : void{
        if ($this->command == null) {
            $this->command = new PoolCommand($this, 'startkit');
            $this->command->createSubCommand(OpenSubCommand::class);
            $this->command->createSubCommand(ResetSubCommand::class);
            $this->command->createSubCommand(LangSubCommand::class);
            $this->command->createSubCommand(ReloadSubCommand::class);
            $this->command->createSubCommand(SaveSubCommand::class);
        }
        $this->command->updateTranslation();
        $this->command->updateSudCommandTranslation();
        if ($this->command->isRegistered()) {
            $this->getServer()->getCommandMap()->unregister($this->command);
        }
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->command);
    }

    public function save() : void{
        if (!file_exists($dataFolder = $this->getDataFolder())) {
            mkdir($dataFolder, 0777, true);
        }

        try{
            $namedTag = new CompoundTag();
            $suppliedList = [];
            foreach ($this->supplieds as $key => $value) {
                $suppliedList[] = new StringTag($value, $value);
            }
            $namedTag->setTag(new ListTag('SuppliedList', $suppliedList, NBT::TAG_String));

            $items = [];
            $inventory = StartKitInventory::getInstance();
            for ($slot = 0; $slot < 27; ++$slot) {
                $item = $inventory->getItem($slot);
                if (!$item->isNull()) {
                    $items[] = $item->nbtSerialize($slot);
                }
            }
            $namedTag->setTag(new ListTag('Kit', $items, NBT::TAG_Compound));

            $nbtStream = new BigEndianNBTStream();
            $nbtStream->setData($namedTag);

            file_put_contents($file = "{$dataFolder}config.dat", $nbtStream->writeCompressed());
        } catch (\Throwable $e){
            $this->getLogger()->warning('Error occurred saving config.dat');
        }
    }

    /** @return String[] */
    public function getSupplieds() : array{
        return $this->supplieds;
    }

    /** @param String[] $supplieds */
    public function setSupplieds(array $supplieds) : void{
        $this->supplieds = $supplieds;
    }

    /**
     * @param string $playerName
     *
     * @return bool
     */
    public function isSupplied(string $playerName) : bool{
        return Utils::in_arrayi($playerName, $this->supplieds);
    }

    /**
     * @param string $playerName
     * @param bool   $supplied = true
     */
    public function setSupplied(string $playerName, bool $supplied = true) : void{
        if ($supplied) {
            if (!$this->isSupplied($playerName)) {
                $this->supplieds[] = $playerName;
            }
        } else {
            for ($i = 0, $count = count($this->supplieds); $i < $count; ++$i) {
                if (strcasecmp($this->supplieds[$i], $playerName) === 0) {
                    unset($this->supplieds[$i]);
                    break;
                }
            }
        }
    }
}
