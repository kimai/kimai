<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Audit;

/**
 * Marks a property as sensitive, so it will not be logged.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class SensitiveProperty
{
}
