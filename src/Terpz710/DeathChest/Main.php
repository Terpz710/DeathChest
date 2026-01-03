<?php

declare(strict_types=1);

namespace Terpz710\DeathChest;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use function count;

class Main extends PluginBase implements Listener {
	private const NBT_TAG = 'DeathChestItems';
	private Config $messages;

	public function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveResource('messages.yml');
		$this->messages = new Config($this->getDataFolder() . 'messages.yml', Config::YAML);
	}

	public function onDeath(PlayerDeathEvent $event) : void {
		$player = $event->getPlayer();
		$drops = $event->getDrops();

		if (count($drops) === 0) {
			return;
		}

		$chest = VanillaBlocks::CHEST()->asItem();
		$chest->setCustomName(TextFormat::RESET . TextFormat::YELLOW . $player->getName() . "'s Loot");

		$nbt = $chest->getNamedTag();
		$itemTags = [];
		$lore = [TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . '(!) ' . TextFormat::RESET . 'Right Click/Tap to claim'];

		foreach ($drops as $item) {
			$itemTags[] = $item->nbtSerialize();
			$lore[] = TextFormat::RESET . $item->getName() . ' x' . $item->getCount();
		}

		$nbt->setTag(self::NBT_TAG, new ListTag($itemTags));
		$chest->setLore($lore);

		$event->setDrops([$chest]);
	}

	public function onInteract(PlayerInteractEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();

		$tag = $item->getNamedTag()->getTag(self::NBT_TAG);
		if ($tag === null || !($tag instanceof ListTag)) {
			return;
		}

		$event->cancel();

		$inventory = $player->getInventory();
		$world = $player->getWorld();
		$position = $player->getPosition();

		foreach ($tag->getValue() as $nbt) {
			if (!$nbt instanceof CompoundTag) {
				continue;
			}

			$deserializedItem = Item::nbtDeserialize($nbt);

			if ($inventory->canAddItem($deserializedItem)) {
				$inventory->addItem($deserializedItem);
			} else {
				$world->dropItem($position, $deserializedItem);
			}
		}

		$newCount = $item->getCount() - 1;
		if ($newCount <= 0) {
			$inventory->setItemInHand($item->setCount(0));
		} else {
			$inventory->setItemInHand($item->setCount($newCount));
		}

		$player->sendMessage($this->messages->get('claimed-message', '§r§l§c(!)§r§f You have claimed the loot!'));
		$player->sendTitle(
			$this->messages->get('claimed-title', '§eLoot Claimed!'),
			$this->messages->get('claimed-subtitle', 'Enjoy the loot:)')
		);
	}
}
