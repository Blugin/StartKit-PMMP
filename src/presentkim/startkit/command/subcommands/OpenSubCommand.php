<?php

namespace presentkim\startkit\command\subcommands;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use presentkim\startkit\StartKit as Plugin;
use presentkim\startkit\command\{
  PoolCommand, SubCommand
};
use presentkim\startkit\inventory\StartKitInventory;
use presentkim\startkit\util\Translation;

class OpenSubCommand extends SubCommand{

    public function __construct(PoolCommand $owner){
        parent::__construct($owner, 'open');
    }

    /**
     * @param CommandSender $sender
     * @param String[]      $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, array $args) : bool{
        if ($sender instanceof Player) {
            $sender->addWindow(StartKitInventory::getInstance());
        } else {
            $sender->sendMessage(Plugin::$prefix . Translation::translate('command-generic-failure@in-game'));
        }
        return true;
    }
}