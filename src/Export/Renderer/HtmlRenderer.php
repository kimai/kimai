<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\Base\HtmlRenderer as BaseHtmlRenderer;
use App\Export\ExportRenderer;
use App\Export\ExportRendererInterface;

final class HtmlRenderer extends BaseHtmlRenderer implements ExportRendererInterface, ExportRenderer
{
}
