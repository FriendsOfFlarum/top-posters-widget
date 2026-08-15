<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\TopPosters\Listener;

use FoF\TopPosters\Job\UpdateTopPostersJob;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Queue\Queue;

class UpdateTopPostersOnSettingsChange
{
    /**
     * @var Queue
     */
    protected $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function handle(Saved $event)
    {
        if (
            isset($event->settings['fof-top-posters-widget.excludeGroups']) ||
            isset($event->settings['fof-top-posters-widget.excludePrivatePosts']) ||
            isset($event->settings['fof-top-posters-widget.calculation_mode']) ||
            isset($event->settings['fof-top-posters-widget.rolling_window_days'])
        ) {
            $this->queue->push(new UpdateTopPostersJob());
        }
    }
}
