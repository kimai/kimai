<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

/**
 * Marker interface for form types which are used by the JSON API.
 *
 * Every form type implementing this interface (including those from plugins) is
 * automatically extended by {@see \App\Form\Extension\ApiFormExtension}.
 */
interface ApiFormInterface
{
}
