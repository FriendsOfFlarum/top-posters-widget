<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->getConnection()
            ->table('settings')
            ->where('key', 'afrux-top-posters-widget.excludeGroups')
            ->update(['key' => 'fof-top-posters-widget.excludeGroups']);
    },

    'down' => function (Builder $schema) {
        $schema->getConnection()
            ->table('settings')
            ->where('key', 'fof-top-posters-widget.excludeGroups')
            ->update(['key' => 'afrux-top-posters-widget.excludeGroups']);
    },
];
