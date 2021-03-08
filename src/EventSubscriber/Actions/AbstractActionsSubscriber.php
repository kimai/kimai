<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber\Actions;

use App\Constants;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

abstract class AbstractActionsSubscriber implements EventSubscriberInterface
{
    private $auth;
    private $urlGenerator;

    public function __construct(AuthorizationCheckerInterface $security, UrlGeneratorInterface $urlGenerator)
    {
        $this->auth = $security;
        $this->urlGenerator = $urlGenerator;
    }

    protected function isGranted($attributes, $subject = null): bool
    {
        return $this->auth->isGranted($attributes, $subject);
    }

    protected function path(string $route, array $parameters = []): string
    {
        return $this->urlGenerator->generate($route, $parameters);
    }

    protected function documentationLink(string $url): string
    {
        return Constants::HOMEPAGE . '/documentation/' . $url;
    }
}
