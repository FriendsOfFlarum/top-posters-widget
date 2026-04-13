import type Mithril from 'mithril';
import Widget, { WidgetAttrs } from 'ext:fof/forum-widgets-core/common/components/Widget';
import type User from 'flarum/common/models/User';
export default class TopPostersWidget extends Widget<WidgetAttrs> {
    private monthlyCounts;
    protected loadWithInitialResponse: boolean;
    oninit(vnode: Mithril.Vnode): void;
    oncreate(vnode: Mithril.Vnode): void;
    className(): string;
    icon(): string;
    title(): string;
    description(): string;
    content(): Mithril.Children;
    load(): void;
    setResults(users: User[]): void;
}
