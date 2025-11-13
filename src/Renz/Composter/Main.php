<?php

/**
 * Composter Plugin for PocketMine-MP 5.37+
 *
 * A fully functional composter block with composting mechanics,
 * bone meal production, and sound effects.
 *
 * @author Renz
 * @version 0.0.1
 * @api 5.0.0
 */

declare(strict_types=1);

namespace Renz\Composter;

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockStateNames;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use Renz\Composter\block\ComposterBlock;

class Main extends PluginBase
{
    protected function onEnable(): void
    {
        $this->getLogger()->info(TextFormat::GREEN . "Enabling Composter Plugin v0.0.1...");
        try {
            $this->registerComposter();
            $this->getLogger()->info(TextFormat::GREEN . "Composter block registered successfully!");
        } catch (\Throwable $e) {
            $this->getLogger()->error(TextFormat::RED . "Failed to register composter: " . $e->getMessage());
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    private function registerComposter(): void
    {
        $composter = ComposterBlock::getInstance();

        $serializer = GlobalBlockStateHandlers::getSerializer();
        $deserializer = GlobalBlockStateHandlers::getDeserializer();

        $deserializer->map(BlockTypeNames::COMPOSTER, function(BlockStateReader $reader): ComposterBlock {
            return (clone ComposterBlock::getInstance())
                ->setComposterLevel($reader->readBoundedInt(BlockStateNames::COMPOSTER_FILL_LEVEL, 0, 8));
        });

        $serializer->map($composter, function(ComposterBlock $block): BlockStateWriter {
            return BlockStateWriter::create(BlockTypeNames::COMPOSTER)
                ->writeInt(BlockStateNames::COMPOSTER_FILL_LEVEL, $block->getComposterLevel());
        });

        RuntimeBlockStateRegistry::getInstance()->register($composter);

        GlobalItemDataHandlers::getDeserializer()->map(
            BlockTypeNames::COMPOSTER,
            fn() => clone $composter->asItem()
        );
        GlobalItemDataHandlers::getSerializer()->map(
            $composter->asItem(),
            fn() => new SavedItemData(BlockTypeNames::COMPOSTER)
        );

        StringToItemParser::getInstance()->register("composter", fn() => clone $composter->asItem());
        CreativeInventory::getInstance()->add($composter->asItem());
    }

    protected function onDisable(): void
    {
        $this->getLogger()->info(TextFormat::YELLOW . "Composter Plugin disabled.");
    }
}
