import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('hawer-s-level', () => {
  // Settings are registered through the Admin extender in ./extend.
});
