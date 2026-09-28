<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel\Listener;

use Flarum\Approval\Event\PostWasApproved;
use Flarum\Discussion\Event as DiscussionEvent;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Post\Event as PostEvent;
use Flarum\Post\Post;
use Flarum\User\User;
use Hawer\SLevel\XpManager;
use Hawer\SLevel\XpRules;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;

class AwardXp
{
    public function __construct(
        protected XpManager $xp,
        protected XpRules $rules,
        protected ConnectionInterface $db
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PostEvent\Posted::class, $this->posted(...));
        $events->listen(PostEvent\Hidden::class, $this->postHidden(...));
        $events->listen(PostEvent\Restored::class, $this->postRestored(...));
        $events->listen(PostEvent\Deleting::class, $this->postDeleting(...));

        $events->listen(DiscussionEvent\Started::class, $this->discussionStarted(...));
        $events->listen(DiscussionEvent\Hidden::class, $this->discussionHidden(...));
        $events->listen(DiscussionEvent\Restored::class, $this->discussionRestored(...));
        $events->listen(DiscussionEvent\Deleting::class, $this->discussionDeleting(...));

        // Optional integrations; these events only fire when the extension is enabled.
        if (class_exists(PostWasLiked::class)) {
            $events->listen(PostWasLiked::class, fn (PostWasLiked $event) => $this->like($event->post, $event->user, 1));
            $events->listen(PostWasUnliked::class, fn (PostWasUnliked $event) => $this->like($event->post, $event->user, -1));
        }

        if (class_exists(PostWasApproved::class)) {
            $events->listen(PostWasApproved::class, $this->postApproved(...));
        }
    }

    public function posted(PostEvent\Posted $event): void
    {
        if ($this->rules->postCounts($event->post)) {
            $this->xp->add($event->post->user, $this->rules->xpPerPost());
        }
    }

    public function postHidden(PostEvent\Hidden $event): void
    {
        $post = $event->post;

        if ($post->type === 'comment' && ! $post->is_private) {
            $this->xp->add($post->user, -$this->rules->postValue($post));
        }
    }

    public function postRestored(PostEvent\Restored $event): void
    {
        if ($this->rules->postCounts($event->post)) {
            $this->xp->add($event->post->user, $this->rules->postValue($event->post));
        }
    }

    /**
     * Uses Deleting rather than Deleted: flarum/likes detaches a post's likes on Deleted,
     * so the received likes can only be counted before that happens.
     */
    public function postDeleting(PostEvent\Deleting $event): void
    {
        if ($this->rules->postCounts($event->post)) {
            $this->xp->add($event->post->user, -$this->rules->postValue($event->post));
        }
    }

    public function postApproved(PostWasApproved $event): void
    {
        $post = $event->post;

        if (! $this->rules->postCounts($post)) {
            return;
        }

        $this->xp->add($post->user, $this->rules->postValue($post));

        if ((int) $post->number === 1 && $post->discussion && $this->rules->discussionCounts($post->discussion)) {
            $this->xp->add($post->discussion->user, $this->rules->xpPerDiscussion());
        }
    }

    public function discussionStarted(DiscussionEvent\Started $event): void
    {
        if ($this->rules->discussionCounts($event->discussion)) {
            $this->xp->add($event->discussion->user, $this->rules->xpPerDiscussion());
        }
    }

    public function discussionHidden(DiscussionEvent\Hidden $event): void
    {
        if (! $event->discussion->is_private) {
            $this->xp->add($event->discussion->user, -$this->rules->xpPerDiscussion());
        }
    }

    public function discussionRestored(DiscussionEvent\Restored $event): void
    {
        if ($this->rules->discussionCounts($event->discussion)) {
            $this->xp->add($event->discussion->user, $this->rules->xpPerDiscussion());
        }
    }

    /**
     * Posts are removed by a cascading foreign key when a discussion is deleted, so no
     * post events fire. Take back the XP for the discussion and all its posts up front.
     */
    public function discussionDeleting(DiscussionEvent\Deleting $event): void
    {
        $discussion = $event->discussion;

        /** @var array<int, int> $deductions user id => xp */
        $deductions = [];

        if ($this->rules->discussionCounts($discussion) && $discussion->user_id) {
            $deductions[$discussion->user_id] = $this->rules->xpPerDiscussion();
        }

        $posts = $this->db->table('posts')
            ->where('discussion_id', $discussion->id)
            ->where('type', 'comment')
            ->where('is_private', false)
            ->whereNull('hidden_at')
            ->whereNotNull('user_id')
            ->pluck('user_id', 'id');

        foreach ($posts as $userId) {
            $deductions[$userId] = ($deductions[$userId] ?? 0) + $this->rules->xpPerPost();
        }

        if ($this->rules->likesInstalled() && $this->rules->xpPerLike() && $posts->isNotEmpty()) {
            $likes = $this->db->table('post_likes')
                ->join('posts', 'posts.id', '=', 'post_likes.post_id')
                ->whereIn('post_likes.post_id', $posts->keys())
                ->whereColumn('post_likes.user_id', '!=', 'posts.user_id')
                ->groupBy('posts.user_id')
                ->selectRaw('posts.user_id as author_id, count(*) as aggregate')
                ->pluck('aggregate', 'author_id');

            foreach ($likes as $userId => $count) {
                $deductions[$userId] = ($deductions[$userId] ?? 0) + $this->rules->xpPerLike() * (int) $count;
            }
        }

        foreach ($deductions as $userId => $amount) {
            $this->xp->add(User::find($userId), -$amount);
        }
    }

    protected function like(Post $post, User $liker, int $direction): void
    {
        if ($post->user_id && (int) $post->user_id !== (int) $liker->id && $this->rules->postCounts($post)) {
            $this->xp->add($post->user, $direction * $this->rules->xpPerLike());
        }
    }
}
