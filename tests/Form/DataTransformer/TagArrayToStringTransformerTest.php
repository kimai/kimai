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
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @covers \App\Form\DataTransformer\TagArrayToStringTransformer
 */
class TagArrayToStringTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $results = [
            (new Tag())->setName('foo'),
            'test',
            (new Tag())->setName('bar'),
        ];

        $repository = $this->getMockBuilder(TagRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new TagArrayToStringTransformer($repository, true);

        $this->assertEquals('', $sut->transform([]));
        $this->assertEquals('', $sut->transform(null));
        $this->assertEquals('', $sut->transform(new \stdClass())); // @phpstan-ignore-line

        $actual = $sut->transform($results); // @phpstan-ignore-line

        $this->assertEquals('foo,test,bar', $actual);
    }

    public function testTransformFails(): void
    {
        $this->expectException(TransformationFailedException::class);
        $results = [
            (new Tag())->setName('foo'),
            new \stdClass(),
            (new Tag())->setName('bar'),
        ];

        $repository = $this->getMockBuilder(TagRepository::class)->disableOriginalConstructor()->getMock();

        $sut = new TagArrayToStringTransformer($repository, true);
        $sut->transform($results); // @phpstan-ignore-line
    }

    public function testReverseTransform(): void
    {
        $results = [
            (new Tag())->setName('foo'),
            (new Tag())->setName('bar'),
        ];

        $repository = $this->getMockBuilder(TagRepository::class)
            ->onlyMethods(['findTagByName', 'saveTag'])
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->exactly(3))->method('findTagByName')->willReturnOnConsecutiveCalls($results[0], $results[1]);

        $sut = new TagArrayToStringTransformer($repository, true);

        $this->assertEquals([], $sut->reverseTransform(''));
        $this->assertEquals([], $sut->reverseTransform(null));

        $actual = $sut->reverseTransform('foo, bar  , hello ');

        $this->assertEquals(array_merge($results, [(new Tag())->setName('hello')]), $actual);
    }
}
