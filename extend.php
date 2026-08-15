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

use Flarum\Api\Serializer as FlarumSerializer;
use Flarum\Api\Controller\ShowForumController;
use Flarum\Extend;
use Flarum\Settings\Event\Saved;
use Flarum\User\Filter\UserFilterer;
use Flarum\User\Search\UserSearcher;
use Illuminate\Console\Scheduling\Event;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ApiSerializer(FlarumSerializer\ForumSerializer::class))
        ->attributes(AddTopPostersToApi::class)
        ->hasMany('topPosters', FlarumSerializer\UserSerializer::class),

    (new Extend\ApiController(ShowForumController::class))
        ->addInclude(['topPosters'])
        ->prepareDataForSerialization(LoadForumTopPostersRelationship::class),

    (new Extend\Filter(UserFilterer::class))
        ->addFilter(Query\TopPosterGambitFilter::class),

    (new Extend\SimpleFlarumSearch(UserSearcher::class))
        ->addGambit(Query\TopPosterGambitFilter::class),

    (new Extend\Settings())
        ->default('fof-top-posters-widget.excludeGroups', '[]')
        ->default('fof-top-posters-widget.timezone', 'UTC')
        ->default('fof-top-posters-widget.excludePrivatePosts', true)
        ->default('fof-top-posters-widget.calculation_mode', 'rolling_window')
        ->default('fof-top-posters-widget.rolling_window_days', 30),

    (new Extend\Routes('api'))
        ->post('/top-posters/recalculate', 'fof.top-posters.recalculate', Api\Controller\RecalculateTopPostersController::class),

    (new Extend\Event())
        ->listen(Saved::class, Listener\UpdateTopPostersOnSettingsChange::class),

    (new Extend\Console())
        ->command(Console\CalculateTopPostersCommand::class)
        ->schedule('fof:top-posters:calculate', function (Event $event) {
            $event->daily();
        }),
];
