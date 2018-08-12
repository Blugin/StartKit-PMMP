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

namespace kim\present\startkit;

use kim\present\startkit\inventory\StartKitInventory;
use kim\present\startkit\lang\PluginLang;
use kim\present\startkit\listener\PlayerEventListener;
use kim\present\startkit\task\CheckUpdateAsyncTask;
use pocketmine\command\{
	Command, CommandExecutor, CommandSender, PluginCommand
};
use pocketmine\nbt\{
	BigEndianNBTStream
};
use pocketmine\nbt\tag\{
	CompoundTag
};
use pocketmine\permission\{
	Permission, PermissionManager
};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class StartKit extends PluginBase implements CommandExecutor{
	public const TAG_PLUGIN = "StartKit";
	public const TAG_INVENTORY = "Inventory";

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

	/**
	 * Called when the plugin is loaded, before calling onEnable()
	 */
	public function onLoad() : void{
		self::$instance = $this;
	}

	/**
	 * Called when the plugin is enabled
	 */
	public function onEnable() : void{
		//Save default resources
		$this->saveResource("lang/eng/lang.ini", false);
		$this->saveResource("lang/kor/lang.ini", false);
		$this->saveResource("lang/language.list", false);

		//Load config file
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$config = $this->getConfig();

		//Check latest version
		if($config->getNested("settings.update-check", false)){
			$this->getServer()->getAsyncPool()->submitTask(new CheckUpdateAsyncTask());
		}

		//Load language file
		$this->language = new PluginLang($this, $config->getNested("settings.language"));
		$this->getLogger()->info($this->language->translate("language.selected", [$this->language->getName(), $this->language->getLang()]));

		//Register main command
		$this->command = new PluginCommand($config->getNested("command.name"), $this);
		$this->command->setPermission("startkit.cmd");
		$this->command->setAliases($config->getNested("command.aliases"));
		$this->command->setUsage($this->language->translate("commands.startkit.usage"));
		$this->command->setDescription($this->language->translate("commands.startkit.description"));
		$this->getServer()->getCommandMap()->register($this->getName(), $this->command);

		//Load permission's default value from config
		$permissions = PermissionManager::getInstance()->getPermissions();
		$defaultValue = $config->getNested("permission.main");
		if($defaultValue !== null){
			$permissions["startkit.cmd"]->setDefault(Permission::getByName($config->getNested("permission.main")));
		}
		if(!file_exists($dataFolder = $this->getDataFolder())){
			mkdir($dataFolder, 0777, true);
		}

		//Load startkit supplied list data
		if(file_exists($file = "{$dataFolder}kit.dat")){
			try{
				$namedTag = (new BigEndianNBTStream())->readCompressed(file_get_contents($file));
				if($namedTag instanceof CompoundTag){
					StartKitInventory::nbtDeserialize($namedTag->getListTag(self::TAG_INVENTORY));
				}else{
					$this->getLogger()->critical("Invalid data found in \"config.dat\", expected " . CompoundTag::class . ", got " . (is_object($namedTag) ? get_class($namedTag) : gettype($namedTag)));
				}
			}catch(\Throwable $e){
				rename($file, "{$file}.bak");
				$this->getLogger()->warning("Error occurred loading kit.dat");
			}
		}

		//Register event listeners
		$this->getServer()->getPluginManager()->registerEvents(new PlayerEventListener($this), $this);
	}

	/**
	 * Called when the plugin is disabled
	 * Use this to free open things and finish actions
	 */
	public function onDisable() : void{
		try{
			file_put_contents("{$this->getDataFolder()}kit.dat", (new BigEndianNBTStream())->writeCompressed(new CompoundTag("StartKit", [
				StartKitInventory::getInstance()->nbtSerialize(self::TAG_INVENTORY),
			])));
		}catch(\Throwable $e){
			$this->getLogger()->warning("Error occurred saving kit.dat");
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
		if($sender instanceof Player){
			$sender->addWindow(StartKitInventory::getInstance());
		}else{
			$sender->sendMessage($this->language->translate("commands.generic.onlyPlayer"));
		}
		return true;
	}

	/**
	 * @Override for multilingual support of the config file
	 *
	 * @return bool
	 */
	public function saveDefaultConfig() : bool{
		$resource = $this->getResource("lang/{$this->getServer()->getLanguage()->getLang()}/config.yml");
		if($resource === null){
			$resource = $this->getResource("lang/" . PluginLang::FALLBACK_LANGUAGE . "/config.yml");
		}

		if(!file_exists($configFile = $this->getDataFolder() . "config.yml")){
			$ret = stream_copy_to_stream($resource, $fp = fopen($configFile, "wb")) > 0;
			fclose($fp);
			fclose($resource);
			return $ret;
		}
		return false;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function isSupplied(Player $player) : bool{
		return $player->namedtag->hasTag(self::TAG_PLUGIN);
	}

	/**
	 * @param Player $player
	 * @param bool   $supplied = true
	 */
	public function setSupplied(Player $player, bool $supplied = true) : void{
		if($supplied){
			$player->namedtag->setByte(self::TAG_PLUGIN, 1);
		}else{
			$player->namedtag->removeTag(self::TAG_PLUGIN);
		}
	}

	/**
	 * @return PluginLang
	 */
	public function getLanguage() : PluginLang{
		return $this->language;
	}
}
