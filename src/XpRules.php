<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;

/**
 * Decides how much XP a post or discussion is worth.
 *
 * Only public, visible content counts: comment posts / discussions that are
 * not hidden and not private (e.g. awaiting approval). Likes only count when
 * they come from someone other than the author.
 */
class XpRules
{
    protected ?bool $likesInstalled = null;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected ConnectionInterface $db
    ) {
    }

    public function xpPerPost(): int
    {
        return (int) $this->settings->get('hawer-s-level.xp_per_post', 10);
    }

    public function xpPerDiscussion(): int
    {
        return (int) $this->settings->get('hawer-s-level.xp_per_discussion', 20);
    }

    public function xpPerLike(): int
    {
        return (int) $this->settings->get('hawer-s-level.xp_per_like', 5);
    }

    public function postCounts(Post $post): bool
    {
        return $post->type === 'comment' && ! $post->is_private && ! $post->hidden_at;
    }

    public function discussionCounts(Discussion $discussion): bool
    {
        return ! $discussion->is_private && ! $discussion->hidden_at;
    }

    /**
     * Total XP a post is currently worth to its author: the post itself plus received likes.
     */
    public function postValue(Post $post): int
    {
        return $this->xpPerPost() + $this->xpPerLike() * $this->likesReceived($post);
    }

    public function likesInstalled(): bool
    {
        return $this->likesInstalled ??= $this->db->getSchemaBuilder()->hasTable('post_likes');
    }

    protected function likesReceived(Post $post): int
    {
        if (! $this->likesInstalled() || $this->xpPerLike() === 0) {
            return 0;
        }

        return $this->db->table('post_likes')
            ->where('post_id', $post->id)
            ->when($post->user_id, fn ($query) => $query->where('user_id', '!=', $post->user_id))
            ->count();
    }
}
