<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/**
 * A simple class that is available in twig renderer context, which can be used to define global renderer options.
 */
final class ExportContext
{
    private $options = [];

    public function __construct()
    {
        $this->initDefaults();
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return void
     */
    public function setOption(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $key
     * @return array|string|null
     */
    public function getOption(string $key)
    {
        if (\array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return null;
    }

    /**
     * @return void
     */
    private function initDefaults(): void
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();

        $this->setOption('fontDir', $defaultConfig['fontDir']);
        $this->setOption('fontdata', $defaultFontConfig['fontdata']);
    }
}
