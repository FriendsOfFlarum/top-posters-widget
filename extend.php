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
use Flarum\User\Event\Saving as UserSaving;
use Flarum\User\Filter\UserFilterer;
use Flarum\User\Search\UserSearcher;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    // @TODO: Replace with the new implementation https://docs.flarum.org/2.x/extend/api#extending-api-resources
    (new Extend\ApiSerializer(FlarumSerializer\ForumSerializer::class))
        ->attributes(AddTopPostersToApi::class)
        ->hasMany('topPosters', FlarumSerializer\UserSerializer::class),

    // @TODO: Replace with the new implementation https://docs.flarum.org/2.x/extend/api#extending-api-resources
    (new Extend\ApiController(ShowForumController::class))
        ->addInclude(['topPosters'])
        ->prepareDataForSerialization(LoadForumTopPostersRelationship::class),

    (new Extend\Filter(UserFilterer::class))
        ->addFilter(Query\TopPosterGambitFilter::class),

    (new Extend\SimpleFlarumSearch(UserSearcher::class))
        ->addGambit(Query\TopPosterGambitFilter::class),

    (new Extend\Settings())
        ->default('fof-top-posters-widget.excludeGroups', '[]'),

    (new Extend\Event())
        ->listen(Saved::class, Listener\ClearTopPosterCacheOnSettingsChange::class)
        ->listen(UserSaving::class, Listener\ClearTopPosterCacheOnSuspension::class),
];
