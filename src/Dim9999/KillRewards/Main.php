<?php

declare(strict_types=1);

namespace Dim9999\KillRewards;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
    }

    private function rewardItems(Player $player): void
    {
        $items = $this->getConfig()->get("items");
        if(count($items) === 0 or $items === ""){
            return;
        }
        if (!$this->getConfig()->get("random-items")) {
            foreach ($this->getConfig()->get("items") as $item) {
                $item = $item;
                $data = explode(":", $item);
                $item = Item::get((int)$data[0], (int)$data[1], (int)$data[2]);
                if (isset($data[3])) {
                    $item->setCustomName((string)$data[3]);
                }
                $player->getInventory()->addItem(Item::get((int)$data[0], (int)$data[1], (int)$data[2]));
            }
        } else {
            $item = array_rand($this->getConfig()->get("items"), 1);
            $item = $items[$item];
            $data = explode(":", $item);
            $item = Item::get((int)$data[0], (int)$data[1], (int)$data[2]);
            if (isset($data[3])) {
                $item->setCustomName((string)$data[3]);
            }
            $player->getInventory()->addItem(Item::get((int)$data[0], (int)$data[1], (int)$data[2]));
        }
    }

    private function rewardMoney(Player $player): void
    {
        $api = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if (!$api) {
            return;
        }
        if ($this->getConfig()->get("economyapi-support")) {
            if (!$this->getConfig()->get("enable-random")) {
                $api->addMoney($player, $this->getConfig()->get("money"));
            } else {
                $api->addMoney($player, mt_rand((int)$this->getConfig()->get("min-money"), (int)$this->getConfig()->get("max-money")));
            }
        }
    }

    /**
     * @param Player $player
     */
    private function rewardCommands(Player $player): void
    {
        $commands = $this->getConfig()->get("commands");
        if(count($commands) === 0 or $commands === ""){
            return;
        }
        if (!$this->getConfig()->get("random-command")) {
            foreach ($this->getConfig()->get("commands") as $command) {
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{player}", '"' . $player->getName() . '"', $command));
            }
        } else {
            $command = array_rand($this->getConfig()->get("commands"), 1);
            $this->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("{player}", '"' . $player->getName() . '"', $commands[$command]));
        }
    }

    /**
     * @param EntityDamageEvent $event
     */
    public function onDamage(EntityDamageEvent $event): void
    {
        if ($event->isCancelled()) {
            return;
        }
        $player = $event->getEntity();
        if ($player instanceof Player and $event instanceof EntityDamageByEntityEvent and $event->getFinalDamage() >= $player->getHealth()) {
            $killer = $event->getDamager();
            if ($killer instanceof Player) {
                if (!$this->getConfig()->get("enable-luck") or ($this->getConfig()->get("enable-luck") and mt_rand((int)$this->getConfig()->get("min"), (int)$this->getConfig()->get("max")) === (int)$this->getConfig()->get("value"))) {
                    $this->rewardCommands($killer);
                    $this->rewardItems($killer);
                    $this->rewardMoney($killer);
                }
                if ($this->getConfig()->get("enable-messages")) {
                    $killer->sendMessage(str_replace(["{killer}", "{player}"], [$killer->getName(), $player->getName()], $this->getConfig()->get("killer-message")));
                    $player->sendMessage(str_replace(["{killer}", "{player}"], [$killer->getName(), $player->getName()], $this->getConfig()->get("player-message")));
                }
            }
        }
    }
}
