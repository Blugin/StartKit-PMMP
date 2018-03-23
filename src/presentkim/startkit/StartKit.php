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

    /** @var StartKit */
    private static $instance = null;

    /** @var string */
    public static $prefix = '';

    /** @return StartKit */
    public static function getInstance() : StartKit{
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
                $namedTag = (new BigEndianNBTStream())->readCompressed(file_get_contents($file));
                if ($namedTag instanceof CompoundTag) {
                    $this->supplieds = $namedTag->getListTag('SuppliedList')->getAllValues();
                    StartKitInventory::nbtDeserialize($namedTag->getListTag('Kit'));
                } else {
                    $this->getLogger()->critical("Invalid data found in \"config.dat\", expected " . CompoundTag::class . ", got " . (is_object($namedTag) ? get_class($namedTag) : gettype($namedTag)));
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
            file_put_contents("{$dataFolder}config.dat", (new BigEndianNBTStream())->writeCompressed(new CompoundTag('StartKit', [
              new ListTag('SuppliedList', array_map(function (String $value){
                  return new StringTag($value, $value);
              }, array_values($this->supplieds)), NBT::TAG_String),
              StartKitInventory::getInstance()->nbtSerialize(),
            ])));
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

    /**
     * @param string $name = ''
     *
     * @return PoolCommand
     */
    public function getCommand(string $name = '') : PoolCommand{
        return $this->command;
    }

    /** @param PoolCommand $command */
    public function setCommand(PoolCommand $command) : void{
        $this->command = $command;
    }
}
