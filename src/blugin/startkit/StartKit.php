<?php

namespace blugin\startkit;

use pocketmine\plugin\PluginBase;
use pocketmine\command\{
    Command, PluginCommand, CommandExecutor, CommandSender
};
use pocketmine\nbt\{
  NBT, BigEndianNBTStream
};
use pocketmine\nbt\tag\{
  CompoundTag, ListTag, StringTag
};
use blugin\startkit\inventory\StartKitInventory;
use blugin\startkit\listener\PlayerEventListener;
use blugin\startkit\lang\PluginLang;

class StartKit extends PluginBase implements CommandExecutor{

    /** @var StartKit */
    private static $instance = null;

    /** @return StartKit */
    public static function getInstance() : StartKit{
        return self::$instance;
    }

    /** @var PluginCommand */
    private $command;

    /** @var PluginLang */
    private $language;

    /** @var String[] */
    private $supplieds = [];

    public function onLoad() : void{
        self::$instance = $this;
    }

    public function onEnable() : void{
        if (!file_exists($dataFolder = $this->getDataFolder())) {
            mkdir($dataFolder, 0777, true);
        }
        $this->language = new PluginLang($this);

        if ($this->command == null) {
            $this->command = new PluginCommand($this, 'startkit');
            $this->command->createSubCommand(OpenSubCommand::class);
            $this->command->createSubCommand(ResetSubCommand::class);
        }
        $this->command->updateTranslation();
        $this->command->updateSudCommandTranslation();
        if ($this->command->isRegistered()) {
            $this->getServer()->getCommandMap()->unregister($this->command);
        }
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), $this->command);

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

        $this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener(), $this);
    }

    public function onDisable() : void{
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

    /**
     * @param CommandSender $sender
     * @param Command       $command
     * @param string        $label
     * @param string[]      $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($sender instanceof Player) {
            $sender->addWindow(StartKitInventory::getInstance());
        } else {
            $sender->sendMessage($this->language->translate('commands.generic.onlyPlayer'));
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
     * @return PluginCommand
     */
    public function getCommand(string $name = '') : PluginCommand{
        return $this->command;
    }

    /**
     * @return PluginLang
     */
    public function getLanguage() : PluginLang{
        return $this->language;
    }

    /**
     * @return string
     */
    public function getSourceFolder() : string{
        $pharPath = \Phar::running();
        if (empty($pharPath)) {
            return dirname(__FILE__, 4) . DIRECTORY_SEPARATOR;
        } else {
            return $pharPath . DIRECTORY_SEPARATOR;
        }
    }
}
