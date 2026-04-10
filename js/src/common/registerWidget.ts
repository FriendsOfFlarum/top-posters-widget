import Application from 'flarum/common/Application';
import Widgets from 'flarum/extensions/fof-forum-widgets-core/common/extend/Widgets';

import TopPostersWidget from './components/TopPostersWidget';

export default function (app: Application) {
  new Widgets()
    .add({
      key: 'topPosters',
      component: TopPostersWidget,
      isDisabled: () => {
        const loadWithInitialResponse = app.forum.attribute('fof-forum-widgets-core.preferDataWithInitialLoad');
        const monthlyCounts = app.forum.attribute('fof-top-posters-widget.topPosterCounts');

        return (!loadWithInitialResponse && !app.forum.attribute('canSearchUsers')) || !monthlyCounts || !Object.keys(monthlyCounts).length;
      },
      isUnique: true,
      placement: 'end',
      position: 3,
    })
    .extend(app, 'fof-top-posters-widget');
}
