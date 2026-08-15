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

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;

class TopPostersCalculator
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Cache
     */
    protected $cache;

    protected $hasRecipientsTable = null;

    public function __construct(SettingsRepositoryInterface $settings, Cache $cache)
    {
        $this->settings = $settings;
        $this->cache = $cache;
    }

    public function calculate()
    {
        $this->calculateCurrentMonth();
    }

    public function calculateCurrentMonth()
    {
        $this->calculateMonth(CarbonImmutable::now($this->getTimezone())->format('Y-m'));
    }

    public function calculateAll()
    {
        foreach ($this->getHistoricalMonthKeys() as $monthKey) {
            $this->calculateMonth($monthKey);
        }
    }

    public function calculateMonth($monthKey)
    {
        $excludeGroups = $this->getExcludeGroups();
        $excludePrivate = (bool) $this->settings->get('fof-top-posters-widget.excludePrivatePosts', true);
        $mode = $this->settings->get('fof-top-posters-widget.calculation_mode', 'calendar_month');

        if ($mode === 'rolling_window') {
            $days = (int) $this->settings->get('fof-top-posters-widget.rolling_window_days', 30);

            $currentMonthKey = CarbonImmutable::now($this->getTimezone())->format('Y-m');

            if ($monthKey === $currentMonthKey) {
                $endOfWindow = CarbonImmutable::now($this->getTimezone());
            } else {
                $endOfWindow = CarbonImmutable::createFromFormat('!Y-m', $monthKey, $this->getTimezone())->endOfMonth();
            }

            $startOfWindow = $endOfWindow->subDays($days);
            $start = $startOfWindow->setTimezone('UTC')->toDateTimeString();
            $end = $endOfWindow->setTimezone('UTC')->toDateTimeString();
        } else {
            list($start, $end) = $this->getMonthBounds($monthKey);
        }

        $counts = CommentPost::query()
            ->selectRaw('user_id, count(id) as count')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->whereNull('hidden_at')
            ->whereNotNull('user_id')
            ->when($excludePrivate, function ($query) {
                $query->where('is_private', false);
                if ($this->hasRecipientsTable()) {
                    $query->whereNotExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('recipients')
                            ->whereColumn('recipients.discussion_id', 'posts.discussion_id')
                            ->whereNull('recipients.removed_at');
                    });
                }
            })
            ->when(! empty($excludeGroups), function ($query) use ($excludeGroups) {
                $query->whereNotExists(function ($subQuery) use ($excludeGroups) {
                    $subQuery->selectRaw('1')
                        ->from('group_user')
                        ->whereColumn('group_user.user_id', 'posts.user_id')
                        ->whereIn('group_user.group_id', $excludeGroups);
                });
            })
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get()
            ->mapWithKeys(function (CommentPost $post) {
                return [$post->user_id => (int) $post->getAttribute('count')];
            })
            ->toArray();

        $topUserIds = array_keys($counts);
        foreach ($counts as $userId => $postCount) {
            TopPosterHistory::query()->updateOrCreate(
                ['user_id' => $userId, 'date_month' => $monthKey],
                ['post_count' => $postCount]
            );
        }

        TopPosterHistory::query()
            ->where('date_month', $monthKey)
            ->whereNotIn('user_id', $topUserIds)
            ->delete();

        $this->forgetCurrentMonthCache();
    }

    protected function getHistoricalMonthKeys()
    {
        $timezone = $this->getTimezone();
        $firstPostCreatedAt = CommentPost::query()
            ->whereNull('hidden_at')
            ->whereNotNull('user_id')
            ->min('created_at');

        if (! $firstPostCreatedAt) {
            return [CarbonImmutable::now($timezone)->format('Y-m')];
        }

        $monthKeys = [];
        $cursor = CarbonImmutable::parse($firstPostCreatedAt, 'UTC')
            ->setTimezone($timezone)
            ->startOfMonth();
        $lastMonth = CarbonImmutable::now($timezone)->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $monthKeys[] = $cursor->format('Y-m');
            $cursor = $cursor->addMonth();
        }

        return $monthKeys;
    }

    protected function getMonthBounds($monthKey)
    {
        $monthStart = CarbonImmutable::createFromFormat('!Y-m', $monthKey, $this->getTimezone())->startOfMonth();

        return [
            $monthStart->setTimezone('UTC')->toDateTimeString(),
            $monthStart->addMonth()->setTimezone('UTC')->toDateTimeString(),
        ];
    }

    protected function getExcludeGroups()
    {
        $decoded = json_decode($this->settings->get('fof-top-posters-widget.excludeGroups', '[]'), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_map('intval', $decoded);
    }

    protected function forgetCurrentMonthCache()
    {
        $currentMonthKey = Carbon::now($this->getTimezone())->format('Y-m');

        $this->cache->forget("fof-top-posters-widget.top_poster_counts.{$currentMonthKey}");
    }

    protected function getTimezone()
    {
        return $this->settings->get('fof-top-posters-widget.timezone', 'UTC');
    }

    protected function hasRecipientsTable()
    {
        if ($this->hasRecipientsTable === null) {
            $this->hasRecipientsTable = CommentPost::getConnectionResolver()->connection()->getSchemaBuilder()->hasTable('recipients');
        }

        return $this->hasRecipientsTable;
    }
}
