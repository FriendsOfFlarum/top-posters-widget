<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\TopPosters\Query;

use FoF\TopPosters\UserRepository;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class TopPosterFilter implements FilterInterface
{
    public function __construct(private UserRepository $repository) {}

    public function apply(SearchState $search, $bit): bool
    {
        return parent::apply($search, $bit);
    }

    protected function conditions(SearchState $search, array $matches, $negate): void
    {
        $this->constrain($search->getQuery(), $negate);
    }

    public function getFilterKey(): string
    {
        return 'top_poster';
    }

    public function filter(SearchState $state, array|string $value, bool $negate): void
    {
        $this->constrain($state->getQuery(), $negate);
    }

    protected function constrain(\Illuminate\Database\Eloquent\Builder $query, bool $actor = false): void
    {
        $ids = array_keys($this->repository->getTopPosters());

        if ($actor) {
            $query->whereNotIn('id', $ids);
        } else {
            $query->whereIn('id', $ids);
        }
    }
}
