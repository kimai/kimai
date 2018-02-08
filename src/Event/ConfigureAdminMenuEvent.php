<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Avanzu\AdminThemeBundle\Model\MenuItemModel;

/**
 * The ConfigureAdminMenuEvent is used for populating the administration navigation.
 */
class ConfigureAdminMenuEvent extends ConfigureMenuEvent
{
    const CONFIGURE = 'app.admin_menu_configure';

    /**
     * This function will either return a MenuItem or null.
     *
     * In case this returns null, the user has not the ROLE_ADMIN.
     *
     * @return MenuItemModel|null
     */
    public function getAdminMenu()
    {
        return $this->getMenu()->getRootItem('admin');
    }
}
