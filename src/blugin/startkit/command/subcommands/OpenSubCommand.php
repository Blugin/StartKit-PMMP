<?php

namespace blugin\startkit\command\subcommands;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use blugin\startkit\StartKit as Plugin;
use blugin\startkit\command\{
  PoolCommand, SubCommand
};
use blugin\startkit\inventory\StartKitInventory;
use blugin\startkit\util\Translation;

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