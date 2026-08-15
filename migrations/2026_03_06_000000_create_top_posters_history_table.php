<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        if (! $schema->hasTable('top_posters_history')) {
            $schema->create('top_posters_history', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('post_count')->unsigned();
                $table->string('date_month', 7); // Format: 'YYYY-MM'
    
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['user_id', 'date_month']); // A user can only have one top poster record per month
                $table->index(['date_month', 'post_count']); // Guarantees O(1) read for ordering the top 5 per month
            });
        }
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('top_posters_history');
    }
];
