import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Component from 'flarum/common/Component';
import extractText from 'flarum/common/utils/extractText';

type Scope = 'current' | 'all';

export default class RecalculateButtons extends Component {
  private loadingScope: Scope | null = null;

  view() {
    return (
      <div className="Form-group">
        <h3>{app.translator.trans('fof-top-posters-widget.admin.recalculate.heading')}</h3>
        <div className="helpText">{app.translator.trans('fof-top-posters-widget.admin.recalculate.description')}</div>

        <div className="TopPostersRecalculate-actions">
          <Button
            className="Button"
            icon="fas fa-sync-alt"
            loading={this.loadingScope === 'current'}
            disabled={this.loadingScope !== null}
            onclick={() => {
              void this.recalculate('current');
            }}
          >
            {app.translator.trans('fof-top-posters-widget.admin.recalculate.current')}
          </Button>

          <Button
            className="Button Button--danger"
            icon="fas fa-exclamation-triangle"
            loading={this.loadingScope === 'all'}
            disabled={this.loadingScope !== null}
            onclick={() => {
              void this.recalculate('all');
            }}
          >
            {app.translator.trans('fof-top-posters-widget.admin.recalculate.all')}
          </Button>
        </div>
      </div>
    );
  }

  private async recalculate(scope: Scope): Promise<void> {
    if (scope === 'all') {
      const confirmed = window.confirm(extractText(app.translator.trans('fof-top-posters-widget.admin.recalculate.confirm_all')));

      if (!confirmed) {
        return;
      }
    }

    this.loadingScope = scope;
    m.redraw();

    try {
      await app.request({
        method: 'POST',
        url: this.apiUrl('/top-posters/recalculate'),
        body: { scope },
      });

      app.alerts.show({ type: 'success' }, app.translator.trans(`fof-top-posters-widget.admin.recalculate.success_${scope}`));
    } catch (error: any) {
      app.alerts.show({ type: 'error' }, this.extractError(error) ?? app.translator.trans('fof-top-posters-widget.admin.recalculate.failed'));
    } finally {
      this.loadingScope = null;
      m.redraw();
    }
  }

  private apiUrl(path: string): string {
    const baseUrl = String((app.data as any)?.apiUrl || '/api').replace(/\/$/, '');

    return `${baseUrl}${path}`;
  }

  private extractError(error: any): string | null {
    return error?.response?.errors?.[0]?.detail ?? error?.response?.message ?? error?.message ?? null;
  }
}
