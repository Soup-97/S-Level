import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

const t = (key: string) => app.translator.trans(`hawer-s-level.admin.settings.${key}`);

const number = (key: string, min: number, step?: number) => () => ({
  setting: `hawer-s-level.${key}`,
  type: 'number',
  min,
  step,
  label: t(`${key}_label`),
  help: t(`${key}_help`),
});

export default [
  ...commonExtend,

  new Extend.Admin()
    .setting(number('xp_per_post', 0), 100)
    .setting(number('xp_per_discussion', 0), 90)
    .setting(number('xp_per_like', 0), 80)
    .setting(number('base_xp', 1), 70)
    .setting(number('exponent', 0.1, 0.1), 60)
    .setting(number('max_level', 0), 50)
    .setting(() => ({ setting: 'hawer-s-level.titles', type: 'textarea', rows: 6, label: t('titles_label'), help: t('titles_help') }), 40)
    .setting(() => ({ setting: 'hawer-s-level.show_on_posts', type: 'switch', label: t('show_on_posts_label') }), 30)
    .setting(() => ({ setting: 'hawer-s-level.show_progress', type: 'switch', label: t('show_progress_label') }), 20)
    .setting(() => () => <p className="helpText">{t('recalculate_help')}</p>, 10),
];
