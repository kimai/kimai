<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use JMS\Serializer\Exception\InvalidArgumentException;

/**
 * Annotation class for @Expose().
 *
 * @Annotation
 * @Target({"CLASS", "PROPERTY", "METHOD"})
 */
final class Expose
{
    /**
     * @var string
     * @Required
     */
    public $label;
    /**
     * @var string
     */
    public $name;
    /**
     * @Enum({"string", "datetime", "date", "time", "int", "float", "duration"})
     */
    public $type = 'string';
    /**
     * @var string
     */
    public $exp = null;

    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $this->name = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            if (!property_exists(self::class, $key)) {
                throw new InvalidArgumentException(sprintf('Unknown property "%s" on annotation "%s".', $key, self::class));
            }
            $this->{$key} = $value;
        }
    }
}
