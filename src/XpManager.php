<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel;

use Flarum\User\User;
use Hawer\SLevel\Event\LevelChanged;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Query\Expression;

class XpManager
{
    public function __construct(
        protected LevelCalculator $levels,
        protected Dispatcher $events
    ) {
    }

    /**
     * Atomically add (or, with a negative amount, remove) XP. XP never drops below zero.
     */
    public function add(?User $user, int $amount): void
    {
        if (! $user || ! $user->exists || $amount === 0) {
            return;
        }

        $query = User::query()->whereKey($user->getKey())->toBase();

        $before = (int) (clone $query)->value('level_xp');

        if ($amount > 0) {
            $query->increment('level_xp', $amount);
        } else {
            $amount = abs($amount);
            $query->update([
                'level_xp' => new Expression("CASE WHEN level_xp > $amount THEN level_xp - $amount ELSE 0 END"),
            ]);
        }

        $this->syncUser($user, $before, (int) (clone $query)->value('level_xp'));
    }

    protected function syncUser(User $user, int $before, int $after): void
    {
        $user->level_xp = $after;
        $user->syncOriginalAttribute('level_xp');

        $oldLevel = $this->levels->levelForXp($before);
        $newLevel = $this->levels->levelForXp($after);

        if ($oldLevel !== $newLevel) {
            $this->events->dispatch(new LevelChanged($user, $oldLevel, $newLevel, $after));
        }
    }
}
