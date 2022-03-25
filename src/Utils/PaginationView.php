<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Pagerfanta\View\Template\TemplateInterface;
use Pagerfanta\View\TwitterBootstrap5View;

class PaginationView extends TwitterBootstrap5View
{
    protected function createDefaultTemplate(): TemplateInterface
    {
        return new PaginationTemplate();
    }
}
