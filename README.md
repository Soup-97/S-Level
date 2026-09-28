# S-Level

A level system for [Flarum](https://flarum.org) 2.0 (rc.8 or newer). Users earn XP for taking part in the forum and level up over time.

## Features

- **XP for activity**: posting replies, starting discussions and receiving likes (the likes part needs [Flarum Likes](https://github.com/flarum/likes)).
- **Stays consistent**: XP is taken back when content is hidden or deleted and given back when it is restored. Posts waiting for approval ([Flarum Approval](https://github.com/flarum/approval)) only count once they are approved. Likes on your own posts don't count.
- **Adjustable level curve**: total XP needed for level `L` is `base × (L − 1) ^ exponent`, with an optional maximum level.
- **Level titles**: e.g. `1: Newcomer`, `10: Regular`, `35: Legend`.
- **Where levels show up**: a level badge next to post authors, and the level, title and an XP progress bar on user cards (both the profile page and the hover card).
- **For developers**: `level`, `levelXp`, `levelXpCurrent`, `levelXpNext` and `levelTitle` are added to the `users` API resource, and a `Hawer\SLevel\Event\LevelChanged` event is dispatched whenever a user's level changes.
- **English and German** translations.

### Default curve (base 100, exponent 1.5)

| Level | Total XP |
|------:|---------:|
| 2 | 100 |
| 3 | 283 |
| 5 | 800 |
| 10 | 2,700 |
| 20 | 8,282 |

## Installation

```sh
composer require hawer/flarum-s-level
php flarum migrate
php flarum cache:clear
```

Then enable **S-Level** in the admin panel. To give existing users XP for what they have already posted:

```sh
php flarum s-level:recalculate
```

Run this command again whenever you change the XP-per-action values, because XP that users have already earned is not recalculated automatically.

### Local development

Put the extension in a `packages/` folder in your Flarum install, and add a path repository to the Flarum root `composer.json`:

```json
"repositories": [{ "type": "path", "url": "packages/*" }]
```

Then:

```sh
composer require hawer/flarum-s-level:@dev
cd packages/S-Level/js && npm install && npm run dev
```

## Listening for level changes

```php
use Hawer\SLevel\Event\LevelChanged;

(new Extend\Event())->listen(LevelChanged::class, function (LevelChanged $event) {
    if ($event->isLevelUp()) {
        // $event->user, $event->oldLevel, $event->newLevel, $event->xp
    }
}),
```
