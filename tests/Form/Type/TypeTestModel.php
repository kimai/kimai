<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

/**
 * @extends \ArrayObject<string, mixed>
 */
class TypeTestModel extends \ArrayObject
{
    public function __set(string $name, string|int|null $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __get(string $name): mixed
    {
        return $this->offsetGet($name);
    }
}
