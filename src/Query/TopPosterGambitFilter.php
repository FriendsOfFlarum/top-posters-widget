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
use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class TopPosterGambitFilter extends AbstractRegexGambit implements FilterInterface
{
    public function __construct(private UserRepository $repository) {}

    public function apply(SearchState $search, $bit): bool
    {
        return parent::apply($search, $bit);
    }

    public function getGambitPattern(): string
    {
        return 'is:top_poster';
    }

    protected function conditions(SearchState $search, array $matches, $negate): void
    {
        $this->constrain($search->getQuery(), $negate);
    }

    public function getFilterKey(): string
    {
        return 'top_poster';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate): void
    {
        $this->constrain($filterState->getQuery(), $negate);
    }

    protected function constrain(Builder $query, bool $negate = false): void
    {
        $ids = array_keys($this->repository->getTopPosters());

        if ($negate) {
            $query->whereNotIn('id', $ids);
        } else {
            $query->whereIn('id', $ids);
        }
    }
}
