<?php

namespace presentkim\startkit;

use pocketmine\plugin\PluginBase;
use presentkim\startkit\command\PoolCommand;
use presentkim\startkit\command\subcommands\{
  OpenSubCommand, ResetSubCommand, LangSubCommand, ReloadSubCommand, SaveSubCommand
};
use presentkim\startkit\util\Translation;
use presentkim\startkit\util\Utils;

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
        return Utils::in_arrayi($playerName, $this->supplied);
    }

    /**
     * @param string $playerName
     * @param bool   $supplied = true
     */
    public function setSupplied(string $playerName, bool $supplied = true) : void{
        if ($supplied) {
            if (!$this->isSupplied($playerName)) {
                $this->supplieds[] = $supplied;
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
