<?php

declare(strict_types=1);

namespace kim\present\startkit;

use kim\present\startkit\inventory\StartKitInventory;
use kim\present\startkit\lang\PluginLang;
use kim\present\startkit\listener\PlayerEventListener;
use kim\present\startkit\util\Utils;
use pocketmine\command\{
	Command, CommandExecutor, CommandSender, PluginCommand
};
use pocketmine\nbt\{
	BigEndianNBTStream, NBT
};
use pocketmine\nbt\tag\{
	CompoundTag, ListTag, StringTag
};
use pocketmine\permission\{
	Permission, PermissionManager
};
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

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
		if(file_exists($file = "{$dataFolder}config.dat")){
			try{
				$namedTag = (new BigEndianNBTStream())->readCompressed(file_get_contents($file));
				if($namedTag instanceof CompoundTag){
					$this->supplieds = $namedTag->getListTag("SuppliedList")->getAllValues();
					StartKitInventory::nbtDeserialize($namedTag->getListTag("Kit"));
				}else{
					$this->getLogger()->critical("Invalid data found in \"config.dat\", expected " . CompoundTag::class . ", got " . (is_object($namedTag) ? get_class($namedTag) : gettype($namedTag)));
				}
			}catch(\Throwable $e){
				rename($file, "{$file}.bak");
				$this->getLogger()->warning("Error occurred loading config.dat");
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
			file_put_contents("{$this->getDataFolder()}config.dat", (new BigEndianNBTStream())->writeCompressed(new CompoundTag("StartKit", [
				new ListTag("SuppliedList", array_map(function(String $value){
					return new StringTag($value, $value);
				}, array_values($this->supplieds)), NBT::TAG_String),
				StartKitInventory::getInstance()->nbtSerialize(),
			])));
		}catch(\Throwable $e){
			$this->getLogger()->warning("Error occurred saving config.dat");
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
		if($supplied){
			if(!$this->isSupplied($playerName)){
				$this->supplieds[] = $playerName;
			}
		}else{
			for($i = 0, $count = count($this->supplieds); $i < $count; ++$i){
				if(strcasecmp($this->supplieds[$i], $playerName) === 0){
					unset($this->supplieds[$i]);
					break;
				}
			}
		}
	}

	/**
	 * @return PluginLang
	 */
	public function getLanguage() : PluginLang{
		return $this->language;
	}
}
