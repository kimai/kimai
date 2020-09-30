<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Extractor;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\ExtractorException;
use App\Export\Spreadsheet\Extractor\UserPreferenceExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Export\Spreadsheet\Extractor\UserPreferenceExtractor
 * @covers \App\Export\Spreadsheet\Extractor\ExtractorException
 */
class UserPreferenceExtractorTest extends TestCase
{
    public function testExtract()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnCallback(function (UserPreferenceDisplayEvent $event) {
            $event->addPreference((new UserPreference())->setName('foo')->setEnabled(true));
            $event->addPreference((new UserPreference())->setName('no')->setEnabled(false));
            $event->addPreference((new UserPreference())->setName('bar')->setEnabled(true));
        });

        $sut = new UserPreferenceExtractor($dispatcher);

        $event = new UserPreferenceDisplayEvent('somewhere');

        $columns = $sut->extract($event);

        self::assertIsArray($columns);
        self::assertCount(2, $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $definition = $columns[1];
        self::assertEquals('bar', $definition->getLabel());
        self::assertEquals('string', $definition->getType());
        self::assertEquals('tralalalala', \call_user_func($definition->getAccessor(), (new User())->addPreference((new UserPreference())->setName('bar')->setValue('tralalalala'))));
    }

    public function testCheckType()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $sut = new UserPreferenceExtractor($dispatcher);

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('UserPreferenceExtractor needs a UserPreferenceDisplayEvent instance for work');

        /* @phpstan-ignore-next-line */
        $sut->extract(new \stdClass());
    }
}
