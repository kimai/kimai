<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use App\Plugin\Plugin as CorePlugin;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class Plugin
{
    /**
     * The plugin name, eg. "ExpensesBundle"
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    protected $name;
    /**
     * The plugin version, eg. "1.14"
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     * @Serializer\Type(name="string")
     */
    protected $version;

    public function __construct(CorePlugin $plugin)
    {
        $this->name = $plugin->getId();
        $this->version = $plugin->getMetadata()->getVersion();
    }
}
