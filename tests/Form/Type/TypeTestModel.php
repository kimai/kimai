<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

class TypeTestModel
{
    private $fields = [];

    public function __construct(array $fields = [])
    {
        $this->fields = $fields;
    }

    public function __set($name, $value)
    {
        if (!isset($this->fields[$name])) {
            throw new \InvalidArgumentException('Unknown field: ' . $name);
        }

        $this->fields[$name] = $value;
    }

    public function __get($name)
    {
        if (!isset($this->fields[$name])) {
            throw new \InvalidArgumentException('Unknown field: ' . $name);
        }

        return $this->fields[$name];
    }
}
