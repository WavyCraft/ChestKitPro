<?php

declare(strict_types=1);

namespace wavycraft\chestkit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use pocketmine\utils\TextFormat as TextColor;

use function ucfirst;

use wavycraft\chestkit\utils\KitManager;
use wavycraft\chestkit\utils\CooldownManager;

use wavycraft\core\form\SimpleForm;
use wavycraft\core\form\ModalForm;

class KitCommand extends Command {

    private $kitManager;
    private $cooldownManager;

    public function __construct() {
        parent::__construct("kit");
        $this->setDescription("Access available kits");
        $this->setPermission("chestkit.cmd");

        $this->kitManager = KitManager::getInstance();
        $this->cooldownManager = CooldownManager::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextColor::RED . "This command can only be run by a player...");
            return false;
        }

        $kits = $this->kitManager->getAvailableKits();

        $form = new SimpleForm(function (Player $player, ?int $data) use ($kits) {
            if ($data === null) {
                return;
            }

            $kitNames = array_keys($kits);
            $kitName = $kitNames[$data];
            $kitData = $kits[$kitName];

            if (!$player->hasPermission($kitData["permission"])) {
                $player->sendMessage(TextColor::RED . "You do not have permission to use the " . TextColor::YELLOW . ucfirst($kitName) . " kit" . TextColor::RED . "!");
                return;
            }

            if ($this->cooldownManager->isOnCooldown($player, $kitName)) {
                $timeLeftSeconds = $this->cooldownManager->getCooldown($player, $kitName);
                $timeLeftFormatted = $this->cooldownManager->formatCooldown($timeLeftSeconds);
                $player->sendMessage(TextColor::RED . "This kit is on cooldown..." . TextColor::EOL . TextColor::EOL . "Time left: " . TextColor::YELLOW . $timeLeftFormatted);
                return;
            }

            $this->openConfirmationForm($player, $kitName);
        });

        $form->setTitle(TextColor::BOLD . "Available Kits");
        foreach ($kits as $kitName => $kitData) {
            $form->addButton(TextColor::colorize($kitData["kit_name"]));
        }

        $sender->sendForm($form);
        return true;
    }

    private function openConfirmationForm(Player $player, string $kitName) {
        $form = new ModalForm(function (Player $player, ?bool $data) use ($kitName) {
            if ($data === null) {
                $player->sendMessage(TextColor::RED . "Kit selection canceled...");
                return;
            }

            $chestItem = $this->kitManager->giveChestKit($player, $kitName);
            if ($chestItem !== null) {
                $cooldown = $this->kitManager->getKitCooldown($kitName);
                $this->cooldownManager->setCooldown($player, $kitName, $cooldown);

                $player->sendMessage(TextColor::GREEN . "You have received the chest kit containing the " . TextColor::YELLOW . ucfirst($kitName) . " kit" . TextColor::GREEN . "!");
            } else {
                $player->sendMessage(TextColor::RED . "Failed to create the chest kit. Please contact the owner/developer...");
            }
        });

        $timeLeft = $this->kitManager->getKitCooldown($kitName);
        $timeLeftFormatted = $this->cooldownManager->formatCooldown($timeLeft);

        $form->setTitle(TextColor::BOLD . "Confirmation");
        $form->setContent("Do you want to claim the chest kit containing the " . TextColor::YELLOW . ucfirst($kitName) . " kit" . TextColor::WHITE . "?" . TextColor::EOL . TextColor::EOL . "Cooldown: " . TextColor::YELLOW . $timeLeftFormatted);
        $form->setButton1("Yes");
        $form->setButton2("No");
        $player->sendForm($form);
    }
}