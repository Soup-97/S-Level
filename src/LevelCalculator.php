<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * Converts between total XP and levels.
 *
 * The total XP needed to reach level L is:  base_xp * (L - 1) ^ exponent
 * With the defaults (100, 1.5): L2 = 100, L3 = 283, L5 = 800, L10 = 2700 XP.
 */
class LevelCalculator
{
    /** @var array<int, string>|null threshold level => title, sorted ascending */
    protected ?array $titles = null;

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function baseXp(): float
    {
        return max(1.0, (float) $this->settings->get('hawer-s-level.base_xp', 100));
    }

    public function exponent(): float
    {
        return max(0.1, (float) $this->settings->get('hawer-s-level.exponent', 1.5));
    }

    /**
     * 0 means unlimited.
     */
    public function maxLevel(): int
    {
        return max(0, (int) $this->settings->get('hawer-s-level.max_level', 0));
    }

    public function xpForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return (int) ceil($this->baseXp() * (($level - 1) ** $this->exponent()));
    }

    public function levelForXp(int $xp): int
    {
        $xp = max(0, $xp);

        $level = (int) floor(($xp / $this->baseXp()) ** (1 / $this->exponent())) + 1;

        // Correct any floating point drift of the closed-form inverse.
        while ($level > 1 && $this->xpForLevel($level) > $xp) {
            $level--;
        }
        while ($this->xpForLevel($level + 1) <= $xp) {
            $level++;
        }

        $max = $this->maxLevel();

        return $max > 0 ? min($level, $max) : $level;
    }

    /**
     * XP required for the next level, or null if the user is at the max level.
     */
    public function nextLevelXp(int $level): ?int
    {
        $max = $this->maxLevel();

        if ($max > 0 && $level >= $max) {
            return null;
        }

        return $this->xpForLevel($level + 1);
    }

    public function titleForLevel(int $level): ?string
    {
        $title = null;

        foreach ($this->titles() as $threshold => $name) {
            if ($threshold > $level) {
                break;
            }

            $title = $name;
        }

        return $title;
    }

    /**
     * Parses the titles setting, one "<level>: <title>" entry per line.
     *
     * @return array<int, string>
     */
    protected function titles(): array
    {
        if ($this->titles !== null) {
            return $this->titles;
        }

        $titles = [];

        foreach (preg_split('/\R/', (string) $this->settings->get('hawer-s-level.titles', '')) as $line) {
            if (preg_match('/^\s*(\d+)\s*[:=]\s*(.+?)\s*$/u', $line, $matches)) {
                $titles[(int) $matches[1]] = $matches[2];
            }
        }

        ksort($titles);

        return $this->titles = $titles;
    }
}
