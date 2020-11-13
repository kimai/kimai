<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Renderer;

use App\Export\Base\PDFRenderer as BasePDFRenderer;
use App\Export\ExportRendererInterface;

final class PDFRenderer extends BasePDFRenderer implements ExportRendererInterface
{
    public function getIcon(): string
    {
        return 'pdf';
    }

    public function getTitle(): string
    {
        return 'pdf';
    }
}
