<?php

/*
 * This file is part of hawer/flarum-s-level.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Hawer\SLevel;

use Flarum\Api\Resource\UserResource;
use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ApiResource(UserResource::class))
        ->fields(Api\UserResourceFields::class),

    (new Extend\Event())
        ->subscribe(Listener\AwardXp::class),

    (new Extend\Console())
        ->command(Console\RecalculateXpCommand::class),

    (new Extend\Settings())
        ->default('hawer-s-level.xp_per_post', 10)
        ->default('hawer-s-level.xp_per_discussion', 20)
        ->default('hawer-s-level.xp_per_like', 5)
        ->default('hawer-s-level.base_xp', 100)
        ->default('hawer-s-level.exponent', 1.5)
        ->default('hawer-s-level.max_level', 0)
        ->default('hawer-s-level.titles', "1: Newcomer\n5: Member\n10: Regular\n20: Veteran\n35: Legend")
        ->default('hawer-s-level.show_on_posts', true)
        ->default('hawer-s-level.show_progress', true)
        ->serializeToForum('sLevelShowOnPosts', 'hawer-s-level.show_on_posts', 'boolval')
        ->serializeToForum('sLevelShowProgress', 'hawer-s-level.show_progress', 'boolval'),
];
