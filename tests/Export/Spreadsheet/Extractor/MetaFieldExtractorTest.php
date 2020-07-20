<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Spreadsheet\Extractor;

use App\Entity\Project;
use App\Entity\ProjectMeta;
use App\Event\ProjectMetaDisplayEvent;
use App\Export\Spreadsheet\ColumnDefinition;
use App\Export\Spreadsheet\Extractor\ExtractorException;
use App\Export\Spreadsheet\Extractor\MetaFieldExtractor;
use App\Repository\Query\ProjectQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @covers \App\Export\Spreadsheet\Extractor\MetaFieldExtractor
 * @covers \App\Export\Spreadsheet\Extractor\ExtractorException
 */
class MetaFieldExtractorTest extends TestCase
{
    public function testExtract()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnCallback(function (ProjectMetaDisplayEvent $event) {
            $event->addField((new ProjectMeta())->setName('foo')->setIsVisible(true));
            $event->addField((new ProjectMeta())->setName('no')->setIsVisible(false));
            $event->addField((new ProjectMeta())->setName('bar')->setIsVisible(true));
        });

        $sut = new MetaFieldExtractor($dispatcher);

        $event = new ProjectMetaDisplayEvent(new ProjectQuery(), 'somewhere');

        $columns = $sut->extract($event);

        self::assertIsArray($columns);
        self::assertCount(2, $columns);

        foreach ($columns as $column) {
            self::assertInstanceOf(ColumnDefinition::class, $column);
        }

        $definition = $columns[1];
        self::assertEquals('bar', $definition->getLabel());
        self::assertEquals('string', $definition->getType());
        self::assertEquals('tralalalala', \call_user_func($definition->getAccessor(), (new Project())->setMetaField((new ProjectMeta())->setName('bar')->setValue('tralalalala'))));
    }

    public function testCheckType()
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $sut = new MetaFieldExtractor($dispatcher);

        $this->expectException(ExtractorException::class);
        $this->expectExceptionMessage('MetaFieldExtractor needs a MetaDisplayEventInterface instance for work');

        $sut->extract(new \stdClass());
    }
}
