import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Tooltip from 'flarum/common/components/Tooltip';
import type User from 'flarum/common/models/User';

export interface LevelBadgeAttrs extends ComponentAttrs {
  user: User;
  showTitle?: boolean;
}

/**
 * A compact "Lv. 7 · Regular" pill.
 */
export default class LevelBadge extends Component<LevelBadgeAttrs> {
  view() {
    const { user, showTitle = true } = this.attrs;
    const level = user.attribute<number>('level');

    if (!level) return null;

    const title = user.attribute<string | null>('levelTitle');
    const tooltip = app.translator.trans('hawer-s-level.forum.badge_tooltip', { level, xp: user.attribute<number>('levelXp') }, true);

    return (
      <Tooltip text={tooltip}>
        <span className={'SLevelBadge ' + (this.attrs.className || '')}>
          <span className="SLevelBadge-level">{app.translator.trans('hawer-s-level.forum.badge_label', { level })}</span>
          {showTitle && title ? <span className="SLevelBadge-title">{title}</span> : null}
        </span>
      </Tooltip>
    );
  }
}
