<?php

/**
 * Composter Ready Sound
 * 
 * Played when the composter finishes composting and is ready to harvest
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

class ComposterReadySound implements Sound
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
            LevelSoundEvent::BLOCK_COMPOSTER_READY, 
            $pos, 
            false
        )];
    }
}