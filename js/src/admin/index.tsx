import app from 'flarum/admin/app';
import registerWidget from '../common/registerWidget';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Group from 'flarum/common/models/Group';
import Badge from 'flarum/common/components/Badge';

app.initializers.add('fof/top-posters-widget', () => {
  registerWidget();

  app.registry.for('fof-top-posters-widget').registerSetting(function (this: ExtensionPage) {
    const selected = this.setting('fof-top-posters-widget.excludeGroups', '[]');
    let selectedGroupIds: string[] = JSON.parse(selected());

    return (
      <div className="Form-group EditUserModal-groups">
        <label>{app.translator.trans('fof-top-posters-widget.admin.settings.info')}</label>

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
    );
  });
});
