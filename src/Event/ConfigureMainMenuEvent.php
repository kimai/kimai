<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

/**
 * The ConfigureMainMenuEvent is used for populating the main navigation.
 */
class ConfigureMainMenuEvent extends ConfigureMenuEvent
{
    const CONFIGURE = 'app.main_menu_configure';
}
