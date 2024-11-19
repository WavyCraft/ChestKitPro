<?php

declare(strict_types=1);

namespace wavycraft\chestkit\task;

use pocketmine\scheduler\Task;

use wavycraft\chestkit\utils\CooldownManager;

class CooldownTask extends Task {

    public function onRun() : void{
        $cooldownManager = CooldownManager::getInstance();
        foreach ($cooldownManager->getCooldowns() as $uuid => $kits) {
            foreach ($kits as $kitName => $expiryTime) {
                if ($expiryTime <= time()) {
                    $cooldownManager->removeCooldownByKit($uuid, $kitName);
                }
            }
        }
    }
}