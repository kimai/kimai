<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Event;

use Avanzu\AdminThemeBundle\Event\SidebarMenuEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

/**
 * The ConfigureMenuEvent is used for populating navigations.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
abstract class ConfigureMenuEvent extends Event
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var SidebarMenuEvent
     */
    private $event;
    /**
     * @var AuthorizationChecker
     */
    private $auth;

    /**
     * ConfigureMenuEvent constructor.
     * @param AuthorizationChecker $auth
     * @param Request $request
     * @param SidebarMenuEvent $event
     */
    public function __construct(
        AuthorizationChecker $auth,
        Request $request,
        SidebarMenuEvent $event
    ) {
        $this->auth = $auth;
        $this->request = $request;
        $this->event = $event;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return SidebarMenuEvent
     */
    public function getMenu()
    {
        return $this->event;
    }

    /**
     * @return AuthorizationChecker
     */
    public function getAuth()
    {
        return $this->auth;
    }
}
