import app from 'flarum/admin/app';
import registerWidget from '../common/registerWidget';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Group from 'flarum/common/models/Group';
import Badge from 'flarum/common/components/Badge';
import icon from 'flarum/common/helpers/icon';
import RecalculateButtons from './components/RecalculateButtons';

app.initializers.add('fof/top-posters-widget', () => {
  registerWidget();

  app.extensionData.for('fof-top-posters-widget').registerSetting(function (this: ExtensionPage) {
    const selected = this.setting('fof-top-posters-widget.excludeGroups', '[]');
    let selectedGroupIds: string[] = JSON.parse(selected());
    const calculationMode = this.setting('fof-top-posters-widget.calculation_mode', 'rolling_window');

    return (
      <div className="TopPostersAdminSettings">
        <div className="Form-group">
          <h3>{app.translator.trans('fof-top-posters-widget.admin.settings.calculation_settings_label')}</h3>

          {this.buildSettingComponent({
            type: 'select',
            setting: 'fof-top-posters-widget.calculation_mode',
            label: app.translator.trans('fof-top-posters-widget.admin.settings.calculation_mode_label'),
            options: {
              calendar_month: app.translator.trans('fof-top-posters-widget.admin.settings.mode_calendar'),
              rolling_window: app.translator.trans('fof-top-posters-widget.admin.settings.mode_rolling'),
            },
            default: 'rolling_window',
          })}

          {calculationMode() === 'rolling_window' &&
            this.buildSettingComponent({
              type: 'number',
              setting: 'fof-top-posters-widget.rolling_window_days',
              label: app.translator.trans('fof-top-posters-widget.admin.settings.rolling_days'),
              min: 1,
              max: 365,
            })}
        </div>

        <hr />

        <div className="Form-group EditUserModal-groups">
          <h3>{app.translator.trans('fof-top-posters-widget.admin.settings.exclusion_settings_label')}</h3>

          {this.buildSettingComponent({
            type: 'boolean',
            setting: 'fof-top-posters-widget.excludePrivatePosts',
            label: app.translator.trans('fof-top-posters-widget.admin.settings.exclude_private'),
          })}

          <div className="helpText">{app.translator.trans('fof-top-posters-widget.admin.settings.info')}</div>

          {app.store
            .all<Group>('groups')
            .filter((g: Group) => g.id() !== Group.GUEST_ID)
            .map((g: Group) => (
              <div>
                <label className="checkbox">
                  <input
                    type="checkbox"
                    checked={selectedGroupIds.includes(g.id()!)}
                    onchange={(event: Event) => {
                      const checkbox = event.target as HTMLInputElement;

                      if (checkbox.checked) {
                        selectedGroupIds.push(g.id()!);
                      } else {
                        selectedGroupIds = selectedGroupIds.filter((id) => id !== g.id());
                      }

                      selected(JSON.stringify(selectedGroupIds));
                    }}
                  />
                  <Badge icon={g.icon() || 'fas fa-user'} color={g.color()} /> {g.namePlural()}
                </label>
              </div>
            ))}
        </div>

        <hr />

        <RecalculateButtons />

        <div className="helpText">
          {icon('fas fa-exclamation-circle')} {app.translator.trans('fof-top-posters-widget.admin.recalculate.save_before_recalculate')}
        </div>
      </div>
    );
  });
});
