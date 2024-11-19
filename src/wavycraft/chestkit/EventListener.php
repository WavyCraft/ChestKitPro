<?php

declare(strict_types=1);

namespace wavycraft\chestkit;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\nbt\tag\StringTag;

use pocketmine\player\Player;

use pocketmine\utils\TextFormat as TextColor;

use wavycraft\chestkit\utils\KitManager;

class EventListener implements Listener {

    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();

        if ($item->getNamedTag()->getTag("ChestKit", StringTag::class)) {
            $kitName = $item->getNamedTag()->getString("ChestKit");
            $kitManager = KitManager::getInstance();

            $kit = $kitManager->getKit($kitName);
            if ($kit === null) {
                $player->sendMessage(TextColor::RED . "Kit not found!");
                return;
            }
            $event->cancel();

            if ($kitManager->giveKit($player, $kitName)) {
                $player->sendMessage(TextColor::GREEN . "You have claimed the " . TextColor::YELLOW . ucfirst($kitName) . " kit" . TextColor::GREEN . "!");
            } else {
                $player->sendMessage(TextColor::RED . "Failed to give kit items...");
            }

            $inventory = $player->getInventory();
            $inventory->removeItem($item->setCount(1));
        }
    }
}
