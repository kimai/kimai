<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use KevinPapst\AdminLTEBundle\Model\MenuItemModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * The ConfigureAdminMenuEvent is used for populating the administration navigation.
 */
final class ConfigureAdminMenuEvent extends Event
{
    public const CONFIGURE = 'app.admin_menu_configure';

    /**
     * @var Request
     */
    private $request;
    /**
     * @var MenuItemModel
     */
    private $menu;

    /**
     * @param Request $request
     * @param MenuItemModel $menu
     */
    public function __construct(Request $request, MenuItemModel $menu)
    {
        $this->request = $request;
        $this->menu = $menu;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return MenuItemModel
     */
    public function getAdminMenu()
    {
        return $this->menu;
    }
}
