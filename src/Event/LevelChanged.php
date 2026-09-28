<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel\Event;

use Flarum\User\User;

/**
 * Dispatched whenever a user's level goes up or down because their XP changed.
 * Other extensions can listen to this, e.g. to send notifications or grant groups.
 */
class LevelChanged
{
    public function __construct(
        public User $user,
        public int $oldLevel,
        public int $newLevel,
        public int $xp
    ) {
    }

    public function isLevelUp(): bool
    {
        return $this->newLevel > $this->oldLevel;
    }
}
