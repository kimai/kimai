<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Entities;

use App\Export\Annotation as Exporter;

/**
 * @Exporter\Order({"a-time", "publicProperty", "a-date", "something", "privateProperty"})
 * @Exporter\Expose("accessor", label="label.accessor", exp="object.accessorMethod()")
 * @Exporter\Expose("a-date", label="label.type-date", exp="object.getDateTime()", type="date")
 * @Exporter\Expose("a-time", label="label.type-time", exp="object.getDateTime()", type="time")
 */
class DemoFull
{
    /**
     * @Exporter\Expose(label="label.Public-Property", type="string")
     */
    public $publicProperty = 'public-property';
    /**
     * @Exporter\Expose("fake-name", label="label.Protected-Property", type="boolean")
     */
    protected $protectedProperty = false;
    /**
     * @Exporter\Expose(label="label.Private-Property", type="integer")
     */
    private $privateProperty = 123;

    /**
     * @Exporter\Expose(label="label.Public-Method")
     */
    public function publicMethod(): string
    {
        return 'public-method';
    }

    /**
     * @Exporter\Expose(label="label.Protected-Method", type="datetime")
     */
    protected function protectedMethod(): \DateTime
    {
        return new \DateTime();
    }

    public function getDateTime(): \DateTime
    {
        return new \DateTime();
    }

    /**
     * @Exporter\Expose("renamedDuration", label="label.duration", type="duration")
     */
    protected function duration(): int
    {
        return 12345;
    }

    /**
     * @Exporter\Expose(name="fake-method", label="label.Private-Method", type="boolean")
     */
    private function privateMethod(): bool
    {
        return true;
    }

    public function accessorMethod(): string
    {
        return 'accessor-method';
    }
}
