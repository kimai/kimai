<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class QuickEntryModel extends Constraint
{
    public const ACTIVITY_REQUIRED = 'ya34gh7-dsfef3-1234-5678-2g8jkfr56d82';
    public const PROJECT_REQUIRED = 'ya34gh7-dsfef3-1234-5678-2g8jkfr56d84';

    public $messageActivityRequired = 'An activity needs to be selected';
    public $messageProjectRequired = 'A project needs to be selected';
}
