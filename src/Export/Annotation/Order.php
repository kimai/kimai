<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class Order
{
    /**
     * @var array<string>
     */
    public $order = [];

    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $this->order = $data['value'];
            unset($data['value']);
        }
    }
}
