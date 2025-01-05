<?php

declare(strict_types=1);

namespace wavycraft\chestkit;

use pocketmine\plugin\PluginBase;

use wavycraft\chestkit\command\KitCommand;

use wavycraft\chestkit\task\CooldownTask;

final class Loader extends PluginBase {

    protected static $instance;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->saveResource("kits.yml");
        $this->getServer()->getCommandMap()->register("ChestKitPro", new KitCommand());
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new CooldownTask(), 20);
    }

    public static function getInstance() : self{
        return self::$instance;
   }
}
