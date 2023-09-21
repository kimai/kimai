<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Widget\Type;

use App\Widget\Type\AbstractWidgetType;

/**
 * @covers \App\Tests\Widget\Type\More
 */
class MoreTest extends AbstractWidgetTypeTest
{
    public function createSut(): More
    {
        return new More();
    }

    public function getDefaultOptions(): array
    {
        return [];
    }

    public function testTemplateName(): void
    {
        $sut = new More();
        self::assertEquals('widget/widget-more.html.twig', $sut->getTemplateName());
    }

    public function testData(): void
    {
        $sut = $this->createSut();

        self::assertInstanceOf(AbstractWidgetType::class, $sut->setData(''));

        $sut->setData('slkudfhalksjdhfkljsahdf');
        self::assertEquals('slkudfhalksjdhfkljsahdf', $sut->getData());

        $data = new \stdClass();
        $sut->setData($data);
        self::assertSame($data, $sut->getData());
    }
}
