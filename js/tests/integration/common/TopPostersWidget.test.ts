import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { makeUser } from '@flarum/jest-config/factory';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import path from 'path';
import mq from 'mithril-query';
import app from 'flarum/forum/app';
import TopPostersWidget from '../../../src/common/components/TopPostersWidget';
import forumExtenders from '../../../src/forum/extend';

beforeAll(() => bootstrapForum());

describe('TopPostersWidget', () => {
  /** The users the server ranks, in the order its relationship lists them (by id, not by rank). */
  const serve = (posters: { id: string; username: string; count: number; label: string }[]) => {
    posters.forEach(({ id, username }) =>
      app.store.pushObject(makeUser({ id, attributes: { username, displayName: username, slug: username, joinTime: '2020-05-21T11:51:06+00:00' } }))
    );

    app.forum.pushData({
      attributes: {
        'fof-forum-widgets-core.preferDataWithInitialLoad': true,
        'fof-top-posters-widget.topPosterCounts': Object.fromEntries(posters.map(({ id, label }) => [id, label])),
        'fof-top-posters-widget.topPosterRawCounts': Object.fromEntries(posters.map(({ id, count }) => [id, count])),
      },
      relationships: { topPosters: { data: posters.map(({ id }) => ({ type: 'users', id })) } },
    } as any);
  };

  /** Mounts the widget and redraws once, as Mithril would after `oncreate` starts the load. */
  const mount = () => {
    const widget = mq(TopPostersWidget as any, { state: {} });
    widget.redraw();

    return widget;
  };

  const names = (widget: any): string[] =>
    Array.from(widget.find('.FoF-TopPostersWidget-users-item-name') as any[]).map((node: any) => node.textContent);

  const values = (widget: any): string[] =>
    Array.from(widget.find('.FoF-TopPostersWidget-users-item-value') as any[]).map((node: any) => node.textContent.trim());

  beforeAll(() => {
    app.boot();

    forumExtenders.forEach((extender: any) => extender.extend(app, { name: 'fof-top-posters-widget' }));

    const locale = jsYaml.load(fs.readFileSync(path.resolve(process.cwd(), '../locale/en.yml'), 'utf8')) as object;
    app.translator.addTranslations(flatten(locale));

    app.routes.user = { path: '/u/:username', component: {} } as any;
  });

  describe('ranking', () => {
    it('puts the most active poster first, even when their count is shown abbreviated', () => {
      serve([
        { id: '2', username: 'few', count: 5, label: '5' },
        { id: '3', username: 'most', count: 1500, label: '1.50K' },
        { id: '4', username: 'many', count: 999, label: '999' },
      ]);

      const widget = mount();

      expect(names(widget)).toEqual(['most', 'many', 'few']);
      expect(values(widget)).toEqual(['1.50K', '999', '5']);
    });
  });

  describe('when the top posters have to be fetched', () => {
    beforeEach(() => {
      app.forum.pushData({ attributes: { 'fof-forum-widgets-core.preferDataWithInitialLoad': false } } as any);
    });

    afterEach(() => jest.restoreAllMocks());

    it('stops loading and says so when the request fails', async () => {
      jest.spyOn(app.store, 'find').mockReturnValue(Promise.reject(new Error('Network error')) as any);

      const widget = mount();
      await new Promise((resolve) => setTimeout(resolve, 0));
      widget.redraw();

      expect(widget).not.toHaveElement('.LoadingIndicator');
      expect(widget).toContainRaw('Could not load the top posters.');
    });

    it('tries again the next time it is shown, since its state outlives the page', async () => {
      const state = {};
      const settle = () => new Promise((resolve) => setTimeout(resolve, 0));
      app.store.pushObject(makeUser({ id: '7', attributes: { username: 'back', displayName: 'back', slug: 'back' } }));

      jest.spyOn(app.store, 'find').mockReturnValueOnce(Promise.reject(new Error('Network error')) as any);
      mq(TopPostersWidget as any, { state });
      await settle();

      jest.spyOn(app.store, 'find').mockReturnValueOnce(Promise.resolve([app.store.getById('users', '7')]) as any);
      const widget = mq(TopPostersWidget as any, { state });
      await settle();
      widget.redraw();

      expect(names(widget)).toEqual(['back']);
      expect(widget).not.toContainRaw('Could not load the top posters.');
    });

    it('does not fetch again once it has loaded', async () => {
      const state = {};
      const find = jest.spyOn(app.store, 'find').mockReturnValue(Promise.resolve([]) as any);

      mq(TopPostersWidget as any, { state });
      await new Promise((resolve) => setTimeout(resolve, 0));
      mq(TopPostersWidget as any, { state });

      expect(find).toHaveBeenCalledTimes(1);
    });
  });
});
