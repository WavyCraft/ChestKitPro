<?php

declare(strict_types=1);

namespace wavycraft\chestkit\utils;

use pocketmine\player\Player;

use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

use wavycraft\chestkit\Loader;

final class CooldownManager {
    use SingletonTrait;

    private array $cooldowns = [];
    private $cooldownConfig;

    public function __construct() {
        $this->loadCooldowns();
    }

    private function loadCooldowns() {
        $this->cooldownConfig = new Config(Loader::getInstance()->getDataFolder() . "cooldowns.json", Config::JSON);
        $this->cooldowns = $this->cooldownConfig->getAll();
    }

    public function saveCooldowns() {
        $this->cooldownConfig->setAll($this->cooldowns);
        $this->cooldownConfig->save();
    }

    public function setCooldown(Player $player, string $kitName, int $time) {
        $uuid = $player->getUniqueId()->toString();
        $this->cooldowns[$uuid][$kitName] = time() + $time;
        $this->saveCooldowns();
    }

    public function isOnCooldown(Player $player, string $kitName) : bool{
        $uuid = $player->getUniqueId()->toString();
        return isset($this->cooldowns[$uuid][$kitName]) && $this->cooldowns[$uuid][$kitName] > time();
    }

    public function getCooldowns() : array{
        return $this->cooldowns;
    }

    public function getCooldown(Player $player, string $kitName) : int{
        $uuid = $player->getUniqueId()->toString();
        return max(0, ($this->cooldowns[$uuid][$kitName] ?? 0) - time());
    }

    public function removePlayerCooldowns(Player $player) {
        $uuid = $player->getUniqueId()->toString();
        unset($this->cooldowns[$uuid]);
        $this->saveCooldowns();
    }

    public function removeCooldownByKit(string $uuid, string $kitName) {
        if (isset($this->cooldowns[$uuid][$kitName])) {
            unset($this->cooldowns[$uuid][$kitName]);
            $this->saveCooldowns();
        }
    }

    public function formatCooldown(int $seconds) : string{
        $timeUnits = [
            'year' => 31536000,
            'month' => 2629152,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1,
        ];

        $formatted = [];
        foreach ($timeUnits as $unit => $value) {
            if ($seconds >= $value) {
                $count = (int)($seconds / $value);
                $formatted[] = $count . ' ' . $unit . ($count > 1 ? 's' : '');
                $seconds %= $value;
            }
        }

        return $formatted ? implode(', ', $formatted) : '0 seconds';
    }
}