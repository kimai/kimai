<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\DataTransformer;

use App\Entity\Tag;
use App\Form\DataTransformer\TagArrayToStringTransformer;
use App\Repository\TagRepository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\DataTransformer\TagArrayToStringTransformer
 */
class TagArrayToStringTransformerTest extends TestCase
{
    public function testTransform()
    {
        $results = [
            (new Tag())->setName('foo'),
            (new Tag())->setName('bar'),
        ];

        $repository = $this->getMockBuilder(TagRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new TagArrayToStringTransformer($repository);

        $this->assertEquals('', $sut->transform([]));
        $this->assertEquals('', $sut->transform(null));

        $actual = $sut->transform($results);

        $this->assertEquals('foo, bar', $actual);
    }

    public function testReverseTransform()
    {
        $results = [
            (new Tag())->setName('foo'),
            (new Tag())->setName('bar'),
        ];

        $repository = $this->getMockBuilder(TagRepository::class)->onlyMethods(['findBy'])->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('findBy')->willReturn($results);

        $sut = new TagArrayToStringTransformer($repository);

        $this->assertEquals([], $sut->reverseTransform(''));
        $this->assertEquals([], $sut->reverseTransform(null));

        $actual = $sut->reverseTransform('foo, bar, hello');

        $this->assertEquals(array_merge($results, [(new Tag())->setName('hello')]), $actual);
    }
}
