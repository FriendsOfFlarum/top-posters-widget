<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\TopPosters;

use Flarum\Api\Controller\ShowForumController;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface;

class LoadForumTopPostersRelationship
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UserRepository $repository
    ) {}

    public function __invoke(ShowForumController $controller, &$data, ServerRequestInterface $request): void
    {
        $loadWithInitialResponse = $this->settings->get('fof-forum-widgets-core.prefer_data_with_initial_load', false);

        if (! $loadWithInitialResponse) {
            $data['topPosters'] = [];
            return;
        }

        $actor = RequestUtil::getActor($request);
        $counts = $this->repository->getTopPosters();

        if (empty($counts)) {
            $data['topPosters'] = [];
            return;
        }

        $data['topPosters'] = User::query()
            ->whereVisibleTo($actor)
            ->whereIn('id', array_keys($counts))
            ->get();
    }
}
