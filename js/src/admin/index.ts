import app from 'flarum/admin/app';
import registerWidget from '../common/registerWidget';
import TopPostersSettingsPage from './components/TopPostersSettingsPage';

app.initializers.add('fof/top-posters-widget', () => {
  app.extensionData.for('fof-top-posters-widget').registerPage(TopPostersSettingsPage);
  registerWidget(app);
});
