<?php

/*
 * This file is part of fof/top-posters-widget.
 *
 * Copyright (c) 2021 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\TopPosters\Api\Controller;

use FoF\TopPosters\TopPostersCalculator;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecalculateTopPostersController implements RequestHandlerInterface
{
    /**
     * @var TopPostersCalculator
     */
    protected $calculator;

    public function __construct(TopPostersCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $body = $request->getParsedBody();
        $payload = is_array($body) ? $body : [];
        $scope = isset($payload['scope']) ? $payload['scope'] : 'current';

        if ($scope === 'all') {
            $this->calculator->calculateAll();
        } else {
            $scope = 'current';
            $this->calculator->calculateCurrentMonth();
        }

        return new JsonResponse([
            'data' => [
                'scope' => $scope,
            ],
        ]);
    }
}
