<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Annotation;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class Expose
{
    public string $type = 'string';

    public function __construct(public ?string $name = null, public ?string $label = null, string $type = 'string', public ?string $exp = null, public ?string $translationDomain = null)
    {
        if (!\in_array($type, ['string', 'datetime', 'date', 'time', 'integer', 'float', 'duration', 'boolean', 'array'])) {
            throw new \InvalidArgumentException(\sprintf('Unknown type "%s" on annotation "%s".', $type, self::class));
        }
        $this->type = $type;
    }
}
