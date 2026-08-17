<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Twig\Extension\RuntimeExtensionInterface;

final class QrCodeExtension implements RuntimeExtensionInterface
{
    public function __construct()
    {
    }

    /**
     * @param string $data
     * @param array<string, mixed> $writerOptions
     * @return string
     */
    public function qrCodeDataUriFunction(string $data, array $writerOptions = []): string
    {
        $builder = new Builder(
            new PngWriter(),
            [],
            false,
            $data,
            new Encoding('UTF-8'),
            ErrorCorrectionLevel::Medium,
        );

        return $builder->build()->getDataUri();
    }
}
