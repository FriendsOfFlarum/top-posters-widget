import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import Forum from 'flarum/common/models/Forum';

import registerWidget from '../common/registerWidget';

app.initializers.add('fof/top-posters-widget', () => {
  User.prototype.prettyCommentCount = Model.attribute('fof-top-posters-widget.prettyCommentCount');
  Forum.prototype.topPosters = Model.hasMany('topPosters', User);

  registerWidget(app);
});
