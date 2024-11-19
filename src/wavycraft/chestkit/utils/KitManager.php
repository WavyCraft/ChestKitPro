<?php

declare(strict_types=1);

namespace wavycraft\chestkit\utils;

use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\enchantment\EnchantmentInstance;

use pocketmine\block\VanillaBlocks;

use pocketmine\player\Player;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as TextColor;

use function array_map;

use wavycraft\chestkit\Loader;

use DaPigGuy\PiggyCustomEnchants\enchants\CustomEnchantManager;

final class KitManager {
    use SingletonTrait;

    private $plugin;
    private array $kits = [];

    public function __construct() {
        $this->plugin = Loader::getInstance();
        $this->loadKits();
    }

    private function loadKits() {
        $config = new Config($this->plugin->getDataFolder() . "kits.yml", Config::YAML);
        $kitsConfig = $config->get("kits", []);
        foreach ($kitsConfig as $name => $kitData) {
            $this->kits[$name] = $kitData;
        }
    }

    public function getAvailableKits() : array{
        return $this->kits;
    }

    public function getKit(string $name) : ?array{
        return $this->kits[$name] ?? null;
    }

    public function getKitCooldown(string $name) : int{
        return isset($this->kits[$name]["cooldown"]) ? (int)$this->kits[$name]["cooldown"] : 0;
    }

    public function giveKit(Player $player, string $kitName) : bool{
        $kit = $this->getKit($kitName);
        if ($kit === null) return false;

        if (!$this->hasEnoughSpace($player, $kit)) {
            $player->sendMessage(TextColor::RED . "Your inventory is too full to receive the kit items, Please free up some space...");
            return false;
        }

        $cooldownManager = CooldownManager::getInstance();
        $cooldownManager->setCooldown($player, $kitName, $kit["cooldown"] ?? 0);

        foreach ($kit["armor"] as $armorPiece) {
            foreach ($armorPiece as $type => $armorData) {
                $item = StringToItemParser::getInstance()->parse($armorData["id"]);
                if ($item instanceof Item) {
                    if (isset($armorData["name"])) {
                        $item->setCustomName(TextColor::colorize($armorData["name"]));
                    }

                    if (isset($armorData["lore"])) {
                        $colorizedLore = array_map(static fn($line) => TextColor::colorize($line), (array)$armorData["lore"]);
                        $item->setLore($colorizedLore);
                    }

                    if (isset($armorData['enchantments'])) {
                        foreach ($armorData['enchantments'] as $enchantmentName => $enchantmentLevel) {
                            $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentName);

                            if ($enchantment === null && class_exists(CustomEnchantManager::class)) {
                                $enchantment = CustomEnchantManager::getEnchantmentByName($enchantmentName);
                            }

                            if ($enchantment !== null) {
                                $enchantmentInstance = new EnchantmentInstance($enchantment, (int) $enchantmentLevel);
                                $item->addEnchantment($enchantmentInstance);
                            }
                        }
                    }

                    $player->getInventory()->addItem($item);
                }
            }
        }

        foreach ($kit["items"] as $itemData) {
            $item = StringToItemParser::getInstance()->parse($itemData["id"]);
            if ($item instanceof Item) {
                $item->setCount($itemData["count"]);

                if (isset($itemData["name"])) {
                    $item->setCustomName(TextColor::colorize($itemData["name"]));
                }

                if (isset($itemData["lore"])) {
                    $colorizedLore = array_map(static fn($line) => TextColor::colorize($line), (array)$itemData["lore"]);
                    $item->setLore($colorizedLore);
                }

                if (isset($itemData['enchantments'])) {
                    foreach ($itemData['enchantments'] as $enchantmentName => $enchantmentLevel) {
                        $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentName);

                        if ($enchantment === null && class_exists(CustomEnchantManager::class)) {
                            $enchantment = CustomEnchantManager::getEnchantmentByName($enchantmentName);
                        }

                        if ($enchantment !== null) {
                            $enchantmentInstance = new EnchantmentInstance($enchantment, (int) $enchantmentLevel);
                            $item->addEnchantment($enchantmentInstance);
                        }
                    }
                }

                $player->getInventory()->addItem($item);
            }
        }

        return true;
    }

    public function giveChestKit(Player $player, string $kitName) : ?Item{
        $kit = $this->getKit($kitName);
        if ($kit === null) {
            return null;
        }

        $chest = VanillaBlocks::CHEST()->asItem();
        $chest->setCustomName(TextColor::colorize($kit['kit_name']));
        $colorizedLore = array_map(static fn($line) => TextColor::colorize($line), (array) $kit['kit_lore']);
        $chest->setLore($colorizedLore);
        $nbt = $chest->getNamedTag();
        $nbt->setString("ChestKit", $kitName);
        $chest->setNamedTag($nbt);
        $player->getInventory()->addItem($chest);
        return $chest;
    }

    private function hasEnoughSpace(Player $player, array $kit) : bool{
        $inventory = $player->getInventory();
        $emptySlots = 0;

        for ($i = 0; $i < $inventory->getSize(); $i++) {
            $item = $inventory->getItem($i);
            if ($item->isNull()) {
                $emptySlots++;
            }
        }

        $requiredSlots = count($kit["items"]) + count($kit["armor"]);
        return $emptySlots >= $requiredSlots;
    }
}
