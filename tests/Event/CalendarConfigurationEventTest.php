<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Event;

use App\Event\CalendarConfigurationEvent;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Event\CalendarConfigurationEvent
 */
class CalendarConfigurationEventTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $configuration = [
          'a' => 'b',
          'c' => 1,
          'd' => false,
        ];
        $sut = new CalendarConfigurationEvent($configuration);

        self::assertSame($configuration, $sut->getConfiguration());

        $new_configuration = ['a' => 'new_value'] + $configuration + ['e' => 'should_not_be_set'];
        $sut->setConfiguration($new_configuration);

        unset($new_configuration['e']);
        self::assertSame($new_configuration, $sut->getConfiguration());
    }
}
