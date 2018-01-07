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

/**
 * The ConfigureMainMenuEvent is used for populating the main navigation.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ConfigureMainMenuEvent extends ConfigureMenuEvent
{
    const CONFIGURE = 'app.main_menu_configure';
}
