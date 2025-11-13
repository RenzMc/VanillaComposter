<?php

/**
 * Composter Empty Sound
 * 
 * Played when bone meal is harvested from a ready composter
 * 
 * @author Renz
 * @version 0.0.1
 */

declare(strict_types=1);

namespace Renz\Composter\sound;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\world\sound\Sound;

class ComposterEmptySound implements Sound
{
    
    /**
     * Encode the sound packet for network transmission
     * 
     * @param Vector3 $pos Position where the sound should play
     * @return array Array of packets to send
     */
    public function encode(Vector3 $pos): array
    {
        return [LevelSoundEventPacket::nonActorSound(
            LevelSoundEvent::BLOCK_COMPOSTER_EMPTY, 
            $pos, 
            false
        )];
    }
}