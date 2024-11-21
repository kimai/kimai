<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Entities;

use App\Export\Annotation as Exporter;

#[Exporter\Order(['a-time', 'publicProperty', 'a-date', 'something', 'privateProperty'])]
#[Exporter\Expose(name: 'accessor', label: 'accessor', exp: 'object.accessorMethod()')]
#[Exporter\Expose(name: 'a-date', label: 'type-date', exp: 'object.getDateTime()', type: 'date')]
#[Exporter\Expose(name: 'a-time', label: 'type-time', exp: 'object.getDateTime()', type: 'time', translationDomain: 'foo')]
class DemoFull
{
    #[Exporter\Expose(label: 'Public-Property', type: 'string')]
    public string $publicProperty = 'public-property';
    #[Exporter\Expose(name: 'fake-name', label: 'Protected-Property', type: 'boolean')]
    protected bool $protectedProperty = false;
    #[Exporter\Expose(label: 'Private-Property', type: 'integer', translationDomain: 'test')]
    private int $privateProperty = 123; // @phpstan-ignore property.onlyWritten

    #[Exporter\Expose(label: 'Public-Method')]
    public function publicMethod(): string
    {
        return 'public-method';
    }

    #[Exporter\Expose(label: 'Protected-Method', type: 'datetime')]
    protected function protectedMethod(): \DateTime
    {
        return new \DateTime();
    }

    public function getDateTime(): \DateTime
    {
        return new \DateTime();
    }

    #[Exporter\Expose(name: 'renamedDuration', label: 'duration', type: 'duration')]
    protected function duration(): int
    {
        return 12345;
    }

    // @phpstan-ignore method.unused
    #[Exporter\Expose(name: 'fake-method', label: 'Private-Method', type: 'boolean', translationDomain: 'bar')]
    private function privateMethod(): bool
    {
        return true;
    }

    public function accessorMethod(): string
    {
        return 'accessor-method';
    }
}
