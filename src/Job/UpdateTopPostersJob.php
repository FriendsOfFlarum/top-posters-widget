<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\TopPosters\Job;

use FoF\TopPosters\TopPostersCalculator;
use Flarum\Queue\AbstractJob;

class UpdateTopPostersJob extends AbstractJob
{
    public function handle(TopPostersCalculator $calculator)
    {
        $calculator->calculate();
    }
}
