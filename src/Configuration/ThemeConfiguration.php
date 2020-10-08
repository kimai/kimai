<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

/**
 * @internal will be deprecated soon, use SystemConfiguration instead
 */
class ThemeConfiguration implements SystemBundleConfiguration, \ArrayAccess
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

    public function isAllowTagCreation(): bool
    {
        return (bool) $this->find('tags_create');
    }

    /**
     * Currently unused, as JS selects are always activated.
     * @deprecated since 1.7 will be removed with 2.0
     */
    public function getSelectPicker(): string
    {
        @trigger_error('getSelectPicker() is deprecated and will be removed with 2.0', E_USER_DEPRECATED);

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
