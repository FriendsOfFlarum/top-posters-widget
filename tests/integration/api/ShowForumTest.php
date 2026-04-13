<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\TopPosters\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class ShowForumTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-forum-widgets-core', 'fof-top-posters-widget');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'poster', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'poster@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test', 'user_id' => 2, 'comment_count' => 3],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a</p></t>', 'created_at' => Carbon::now()->subWeek()],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>b</p></t>', 'created_at' => Carbon::now()->subWeek()],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>c</p></t>', 'created_at' => Carbon::now()->subWeek()],
            ],
        ]);
    }

    #[Test]
    public function top_poster_counts_attribute_is_present()
    {
        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('fof-top-posters-widget.topPosterCounts', $json['data']['attributes']);
    }

    #[Test]
    public function top_poster_counts_reflect_post_counts()
    {
        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $counts = $json['data']['attributes']['fof-top-posters-widget.topPosterCounts'];

        // User 2 has 2 posts, user 3 has 1 post
        $this->assertArrayHasKey(2, $counts);
        $this->assertArrayHasKey(3, $counts);
        $this->assertGreaterThan($counts[3], $counts[2]);
    }

    #[Test]
    public function top_posters_relationship_is_empty_by_default()
    {
        // prefer_data_with_initial_load defaults to false
        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEmpty(Arr::get($json, 'data.relationships.topPosters.data'));
    }

    #[Test]
    public function top_posters_relationship_is_populated_when_setting_enabled()
    {
        $this->setting('fof-forum-widgets-core.prefer_data_with_initial_load', true);

        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $included = Arr::get($json, 'data.relationships.topPosters.data', []);
        $this->assertNotEmpty($included);

        $ids = Arr::pluck($included, 'id');
        $this->assertContains('2', $ids);
        $this->assertContains('3', $ids);
    }

    #[Test]
    public function posts_older_than_one_month_are_excluded_from_counts()
    {
        $this->prepareDatabase([
            Post::class => [
                ['id' => 4, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>old</p></t>', 'created_at' => Carbon::now()->subMonths(2)],
            ],
        ]);

        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $counts = $json['data']['attributes']['fof-top-posters-widget.topPosterCounts'];

        // Old post should not be counted; user 3 still only has 1 recent post
        $this->assertEquals(1, $counts[3]);
    }

    #[Test]
    public function users_in_excluded_group_are_omitted_from_counts()
    {
        $this->prepareDatabase([
            'group_user' => [
                ['user_id' => 2, 'group_id' => Group::MODERATOR_ID],
            ],
        ]);

        $this->setting('fof-top-posters-widget.excludeGroups', json_encode([Group::MODERATOR_ID]));

        $response = $this->send($this->request('GET', '/api'));

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $counts = $json['data']['attributes']['fof-top-posters-widget.topPosterCounts'];

        $this->assertArrayNotHasKey(2, $counts);
        $this->assertArrayHasKey(3, $counts);
    }
}
