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
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    private $messages;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("messages.yml");
        $this->messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
    }

    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();

        if ($player instanceof Player) {
            $drop = VanillaBlocks::CHEST()->asItem();
            $drop->setCustomName(TextFormat::RESET . TextFormat::YELLOW . $player->getName() . "'s Loot");
            $nbt = $drop->getNamedTag();
            $tags = [];
            $lore = [];
            foreach ($event->getDrops() as $item) {
                $tags[] = $item->nbtSerialize();
                $lore[] = TextFormat::RESET . $item->getName() . " x" . $item->getCount();
            }

            $nbt->setTag("PlayerItems", new ListTag($tags));
            $drop->setLore(array_merge([TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "(!) " . TextFormat::RESET . "Right Click/Tap to claim"], $lore));
            
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
            $player->sendMessage($this->messages->get("claimed-message"));
            $player->sendTitle($this->messages->get("claimed-title"));
            $player->sendSubTitle($this->messages->get("claimed-subtitle"));
        }
    }
}
