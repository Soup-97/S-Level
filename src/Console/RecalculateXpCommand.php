<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel\Console;

use Flarum\Console\AbstractCommand;
use Hawer\SLevel\XpRules;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Rebuilds every user's XP from existing content, using the same rules as the live
 * listeners. Run it after installing the extension or after changing the XP settings.
 */
class RecalculateXpCommand extends AbstractCommand
{
    public function __construct(
        protected ConnectionInterface $db,
        protected XpRules $rules
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('s-level:recalculate')
            ->setDescription('Recalculate the XP of all users from their posts, discussions and likes.')
            ->addOption('chunk', null, InputOption::VALUE_REQUIRED, 'Users processed per batch', 500);
    }

    protected function fire(): int
    {
        $chunk = max(1, (int) $this->input->getOption('chunk'));
        $updated = 0;

        $this->info('Recalculating XP...');

        $this->db->table('users')->select('id')->orderBy('id')->chunk($chunk, function ($users) use (&$updated) {
            $ids = $users->pluck('id')->all();

            $posts = $this->db->table('posts')
                ->whereIn('user_id', $ids)
                ->where('type', 'comment')
                ->where('is_private', false)
                ->whereNull('hidden_at')
                ->groupBy('user_id')
                ->selectRaw('user_id, count(*) as aggregate')
                ->pluck('aggregate', 'user_id');

            $discussions = $this->db->table('discussions')
                ->whereIn('user_id', $ids)
                ->where('is_private', false)
                ->whereNull('hidden_at')
                ->groupBy('user_id')
                ->selectRaw('user_id, count(*) as aggregate')
                ->pluck('aggregate', 'user_id');

            $likes = collect();

            if ($this->rules->likesInstalled()) {
                $likes = $this->db->table('post_likes')
                    ->join('posts', 'posts.id', '=', 'post_likes.post_id')
                    ->whereIn('posts.user_id', $ids)
                    ->whereColumn('post_likes.user_id', '!=', 'posts.user_id')
                    ->where('posts.type', 'comment')
                    ->where('posts.is_private', false)
                    ->whereNull('posts.hidden_at')
                    ->groupBy('posts.user_id')
                    ->selectRaw('posts.user_id as author_id, count(*) as aggregate')
                    ->pluck('aggregate', 'author_id');
            }

            foreach ($ids as $id) {
                $xp = (int) ($posts[$id] ?? 0) * $this->rules->xpPerPost()
                    + (int) ($discussions[$id] ?? 0) * $this->rules->xpPerDiscussion()
                    + (int) ($likes[$id] ?? 0) * $this->rules->xpPerLike();

                $this->db->table('users')->where('id', $id)->update(['level_xp' => max(0, $xp)]);
                $updated++;
            }
        });

        $this->info("Done. Updated $updated users.");

        return 0;
    }
}
