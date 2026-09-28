<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel\Api;

use Flarum\Api\Schema;
use Flarum\User\User;
use Hawer\SLevel\LevelCalculator;

class UserResourceFields
{
    public function __construct(
        protected LevelCalculator $levels
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Integer::make('levelXp')
                ->get(fn (User $user) => (int) $user->level_xp),

            Schema\Integer::make('level')
                ->get(fn (User $user) => $this->level($user)),

            // Total XP at which the user's current level started.
            Schema\Integer::make('levelXpCurrent')
                ->get(fn (User $user) => $this->levels->xpForLevel($this->level($user))),

            // Total XP needed for the next level (null when at max level).
            Schema\Integer::make('levelXpNext')
                ->nullable()
                ->get(fn (User $user) => $this->levels->nextLevelXp($this->level($user))),

            Schema\Str::make('levelTitle')
                ->nullable()
                ->get(fn (User $user) => $this->levels->titleForLevel($this->level($user))),
        ];
    }

    protected function level(User $user): int
    {
        return $this->levels->levelForXp((int) $user->level_xp);
    }
}
