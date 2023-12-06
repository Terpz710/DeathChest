<?php

declare(strict_types=1);

namespace Terpz710\DeathChest;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();

        if ($player instanceof Player) {
            $drop = VanillaBlocks::CHEST()->asItem();
            $drop->setCustomName(TextFormat::RESET . TextFormat::YELLOW . $player->getName() . "'s Loot");
            $drop->setLore([TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "(!) " . TextFormat::RESET . "Right Click/Tap to claim"]);
            $nbt = $drop->getNamedTag();
            $tags = [];
            foreach ($event->getDrops() as $item) {
                $tags[] = $item->nbtSerialize();
            }
            $nbt->setTag("PlayerItems", new ListTag($tags));
            $event->setDrops([$drop]);
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (!$player instanceof Player) {
            return;
        }
        if ($item->getNamedTag()->getTag("PlayerItems") !== null) {
            $tag = $item->getNamedTag()->getListTag("PlayerItems");

            /** @var CompoundTag $nbt */
            foreach ($tag->getValue() as $nbt) {
                $item = Item::nbtDeserialize($nbt);

                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else {
                    $player->getWorld()->dropItem($player->getPosition(), $item);
                }
            }
            $event->cancel();
            $itemCount = $item->getCount();
            $itemCount--;
            $item->setCount($itemCount);
            $player->getInventory()->setItemInHand($item);
            $player->sendMessage("§r§l§c(!)§r§f You have claimed the loot from the §eDeathChest§f!");
            $player->sendTitle("§eLoot Claimed!", "Enjoy the loot:)"); // Make a message.yml so users can edit the message.
        }
    }
}
