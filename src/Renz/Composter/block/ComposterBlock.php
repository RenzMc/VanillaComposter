<?php

/**
 * Composter Block Implementation
 *
 * A functional composter block that can compost various organic items
 * and produce bone meal. Features multiple compost levels and sound effects.
 *
 * @author Renz
 * @version 0.0.1
 */

declare(strict_types=1);

namespace Renz\Composter\block;

use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Opaque;
use pocketmine\block\BlockTypeIds;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\item\ToolTier;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use Renz\Composter\sound\ComposterEmptySound;
use Renz\Composter\sound\ComposterFillSound;
use Renz\Composter\sound\ComposterFillSuccessSound;
use Renz\Composter\sound\ComposterReadySound;

class ComposterBlock extends Opaque
{
    public const MIN_LEVEL = 0;
    public const MAX_LEVEL = 7;
    protected int $composterLevel = 0;

    private const COMPOSTABLE_ITEMS = [
        ItemTypeIds::BEETROOT_SEEDS      => 30,
        ItemTypeIds::DRIED_KELP         => 30,
        ItemTypeIds::GLOW_BERRIES       => 30,
        ItemTypeIds::MELON_SEEDS        => 30,
        ItemTypeIds::PITCHER_POD        => 30,
        ItemTypeIds::PUMPKIN_SEEDS      => 30,
        ItemTypeIds::SWEET_BERRIES      => 30,
        ItemTypeIds::TORCHFLOWER_SEEDS  => 30,
        ItemTypeIds::WHEAT_SEEDS        => 30,
        ItemTypeIds::APPLE              => 65,
        ItemTypeIds::BEETROOT           => 65,
        ItemTypeIds::CARROT             => 65,
        ItemTypeIds::COCOA_BEANS        => 65,
        ItemTypeIds::MELON              => 65,
        ItemTypeIds::POTATO             => 65,
        ItemTypeIds::WHEAT              => 65,
        ItemTypeIds::BAKED_POTATO       => 85,
        ItemTypeIds::BREAD              => 85,
        ItemTypeIds::COOKIE             => 85,
        ItemTypeIds::PUMPKIN_PIE        => 100,
    ];

    private static ?ComposterBlock $instance = null;

    public function __construct(BlockIdentifier $identifier, string $name, ?BlockTypeInfo $typeInfo = null)
    {
        parent::__construct(
            $identifier,
            $name,
            $typeInfo ?? new BlockTypeInfo(BlockBreakInfo::pickaxe(0.6, ToolTier::WOOD()))
        );
    }

    public static function getInstance(): ComposterBlock
    {
        if (self::$instance === null) {
            self::$instance = new self(
                new BlockIdentifier(BlockTypeIds::newId()),
                "Composter"
            );
        }
        return self::$instance;
    }

    protected function describeBlockOnlyState(RuntimeDataDescriber $w): void
    {
        $w->boundedIntAuto(self::MIN_LEVEL, self::MAX_LEVEL, $this->composterLevel);
    }

    public function getComposterLevel(): int
    {
        return $this->composterLevel;
    }

    public function setComposterLevel(int $level): self
    {
        if ($level < self::MIN_LEVEL || $level > self::MAX_LEVEL) {
            throw new \InvalidArgumentException("Composter level must be between " . self::MIN_LEVEL . " and " . self::MAX_LEVEL . ", got " . $level);
        }
        $this->composterLevel = $level;
        return $this;
    }

    public function isReady(): bool
    {
        return $this->composterLevel >= self::MAX_LEVEL;
    }

    public function isEmpty(): bool
    {
        return $this->composterLevel <= self::MIN_LEVEL;
    }

    public function canAcceptCompost(): bool
    {
        return $this->composterLevel < self::MAX_LEVEL;
    }

    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null, array &$returnedItems = []): bool
    {
        $world = $this->position->getWorld();

        if ($this->isReady()) {
            $count = mt_rand(1, 3);
            $bone = VanillaItems::BONE_MEAL();
            $bone->setCount($count);

            if ($player !== null && !$player->isCreative()) {
                $inv = $player->getInventory();
                $leftovers = $inv->addItem(clone $bone);
                if (!empty($leftovers)) {
                    $world->dropItem($this->position->add(0.5, 0.5, 0.5), $leftovers[0]);
                }
            } else {
                $world->dropItem($this->position->up(), clone $bone);
            }

            $this->setComposterLevel(self::MIN_LEVEL);
            $world->setBlock($this->position, $this);
            $world->addSound($this->position, new ComposterEmptySound());

            return true;
        }

        if ($this->canAcceptCompost() && $this->canCompostItem($item)) {
            return $this->tryCompostItem($item, $player);
        }

        return false;
    }

    private function canCompostItem(Item $item): bool
    {
        return isset(self::COMPOSTABLE_ITEMS[$item->getTypeId()]);
    }

    private function tryCompostItem(Item $item, ?Player $player = null): bool
    {
        $world = $this->position->getWorld();

        if ($player !== null && !$player->isCreative()) {
            $item->pop();
        }

        if ($this->isEmpty()) {
            $world->addSound($this->position, new ComposterFillSuccessSound());
            $this->incrementLevel();
            return true;
        }

        $chance = self::COMPOSTABLE_ITEMS[$item->getTypeId()];
        if (mt_rand(1, 100) <= $chance) {
            $world->addSound($this->position, new ComposterFillSuccessSound());
            $this->incrementLevel();
        } else {
            $world->addSound($this->position, new ComposterFillSound());
        }

        return true;
    }

    private function incrementLevel(): void
    {
        if ($this->composterLevel < self::MAX_LEVEL) {
            $this->composterLevel++;
            $this->position->getWorld()->setBlock($this->position, $this);

            if ($this->composterLevel === self::MAX_LEVEL) {
                $this->position->getWorld()->addSound($this->position, new ComposterReadySound());
            }
        }
    }

    public function onScheduledUpdate(): void
    {
        if ($this->composterLevel === self::MAX_LEVEL) {
            $this->position->getWorld()->setBlock($this->position, $this);
            $this->position->getWorld()->addSound($this->position, new ComposterReadySound());
        }
    }

    public function getFuelTime(): int
    {
        return 300;
    }

    public function getLightLevel(): int
    {
        return $this->composterLevel > 0 ? ($this->composterLevel >= self::MAX_LEVEL ? 9 : 1) : 0;
    }

    public function getStateBitmask(): int
    {
        return 0b1111;
    }
}