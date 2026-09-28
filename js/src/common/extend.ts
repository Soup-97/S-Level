import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';

export default [
  new Extend.Model(User) //
    .attribute<number>('levelXp')
    .attribute<number>('level')
    .attribute<number>('levelXpCurrent')
    .attribute<number | null>('levelXpNext')
    .attribute<string | null>('levelTitle'),
];
