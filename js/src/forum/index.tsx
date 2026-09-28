import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import LevelBadge from './components/LevelBadge';
import LevelProgress from './components/LevelProgress';

export { default as extend } from './extend';

app.initializers.add('hawer-s-level', () => {
  // Level badge next to the author of each post.
  extend('flarum/forum/components/PostUser', 'userViewItems', function (this: any, items: ItemList<Mithril.Children>) {
    if (!app.forum.attribute('sLevelShowOnPosts')) return;

    const user = this.attrs.post?.user?.();

    if (user && user.attribute('level')) {
      items.add('level', <LevelBadge className="PostUser-level" user={user} />, 5);
    }
  });

  // Level, title and XP progress on the user card (profile hero + hover card).
  extend('flarum/forum/components/UserCard', 'infoItems', function (this: any, items: ItemList<Mithril.Children>) {
    const user = this.attrs.user;

    if (!user || !user.attribute('level')) return;

    items.add(
      'level',
      <div className="UserCard-level">
        <LevelBadge user={user} />
        {app.forum.attribute('sLevelShowProgress') ? <LevelProgress user={user} /> : null}
      </div>,
      110
    );
  });
});
