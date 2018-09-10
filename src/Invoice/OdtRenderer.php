<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

/**
 * This class is a none-working test.
 *
 * Unfortunately PHPWord does not support replacing values in OpenOffice documents,
 * so for now we disable this renderer, until we find another framework that supports it.
 */
class OdtRenderer extends AbstractWordRenderer implements RendererInterface
{
    /**
     * @return string[]
     */
    protected function getFileExtensions()
    {
        // replacement in open-office documents doesn't work
        // return ['.odt', '.ott'];
        return [];
    }

    /**
     * @return string
     */
    protected function getContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
}
