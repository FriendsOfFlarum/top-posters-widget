<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\TopPosters\Tests\integration\search;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class TopPosterFilterTest extends TestCase
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
                ['id' => 4, 'username' => 'lurker', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'lurker@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test', 'user_id' => 2, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>a</p></t>', 'created_at' => Carbon::now()->subWeek()],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>b</p></t>', 'created_at' => Carbon::now()->subWeek()],
                // User 4 (lurker) has no posts
            ],
        ]);
    }

    #[Test]
    public function filter_returns_only_top_posters()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['top_poster' => '1']])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $ids = Arr::pluck($json['data'], 'id');

        $this->assertContains('2', $ids);
        $this->assertContains('3', $ids);
        $this->assertNotContains('4', $ids); // lurker has no posts
    }

    #[Test]
    public function negated_filter_excludes_top_posters()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['top_poster' => '-1']])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $ids = Arr::pluck($json['data'], 'id');

        $this->assertNotContains('2', $ids);
        $this->assertNotContains('3', $ids);
        $this->assertContains('4', $ids); // lurker is not a top poster
    }
}
