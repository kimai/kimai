<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

class ThemeConfiguration implements SystemBundleConfiguration
{
    use StringAccessibleConfigTrait;

    public function getPrefix(): string
    {
        return 'theme';
    }

    public function isAutoReloadDatatable(): bool
    {
        return (bool) $this->find('auto_reload_datatable');
    }

    public function getSelectPicker(): string
    {
        return (string) $this->find('select_type');
    }

    public function getTitle(): ?string
    {
        $title = $this->find('branding.title');
        if (null === $title) {
            return null;
        }

        return (string) $title;
    }
}
