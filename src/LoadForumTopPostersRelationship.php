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

use FoF\ForumWidgets\SafeCacheRepositoryAdapter;
use Flarum\Api\Controller\ShowForumController;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface;

class LoadForumTopPostersRelationship
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var SafeCacheRepositoryAdapter
     */
    private $cache;

    /**
     *
     * @var UserRepository
     */
    protected $repository;

    public function __construct(SettingsRepositoryInterface $settings, SafeCacheRepositoryAdapter $cache, UserRepository $repository)
    {
        $this->settings = $settings;
        $this->cache = $cache;
        $this->repository = $repository;
    }

    public function __invoke(ShowForumController $controller, &$data, ServerRequestInterface $request)
    {
        $loadWithInitialResponse = $this->settings->get('fof-forum-widgets-core.prefer_data_with_initial_load', false);

        if (! $loadWithInitialResponse) {
            $data['topPosters'] = [];
            return;
        }

        $actor = RequestUtil::getActor($request);
        $counts = $this->repository->getTopPosters();

        $data['topPosters'] = $this->cache->remember('fof-top-posters-widget.top_poster_users', 2400, function () use ($actor, $counts) {
            return User::query()
                ->whereVisibleTo($actor)
                ->whereIn('id', array_keys($counts))
                ->get();
        }) ?: [];
    }
}
