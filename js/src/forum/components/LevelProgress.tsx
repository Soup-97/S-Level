import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type User from 'flarum/common/models/User';

export interface LevelProgressAttrs extends ComponentAttrs {
  user: User;
}

/**
 * XP progress bar from the start of the current level to the next one.
 */
export default class LevelProgress extends Component<LevelProgressAttrs> {
  view() {
    const user = this.attrs.user;
    const xp = user.attribute<number>('levelXp') || 0;
    const current = user.attribute<number>('levelXpCurrent') || 0;
    const next = user.attribute<number | null>('levelXpNext');
    const maxed = next === null || next === undefined;

    const percent = maxed ? 100 : next > current ? Math.min(100, Math.max(0, ((xp - current) / (next - current)) * 100)) : 0;

    return (
      <div className="SLevelProgress" role="progressbar" aria-valuemin={current} aria-valuemax={maxed ? xp : next} aria-valuenow={xp}>
        <div className="SLevelProgress-bar">
          <div className="SLevelProgress-fill" style={{ width: `${percent}%` }} />
        </div>
        <div className="SLevelProgress-text">
          {maxed
            ? app.translator.trans('hawer-s-level.forum.max_level_text', { xp })
            : app.translator.trans('hawer-s-level.forum.progress_text', { current: xp, next })}
        </div>
      </div>
    );
  }
}
