<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Serializer;

use App\Serializer\Serializer;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface as CoreSerializerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Serializer::class)]
class SerializerTest extends TestCase
{
    public function testSerializeUsesDefaultGroupsAndEnablesMaxDepthChecks(): void
    {
        $capturedContext = null;
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->expects(self::once())
            ->method('serialize')
            ->willReturnCallback(function ($data, string $format, ?SerializationContext $context = null) use (&$capturedContext): string {
                $capturedContext = $context;
                self::assertSame(['foo' => 'bar'], $data);
                self::assertSame('json', $format);

                return '{"foo":"bar"}';
            });

        $sut = new Serializer($core);
        $result = $sut->serialize(['foo' => 'bar'], 'json');

        self::assertSame('{"foo":"bar"}', $result);
        self::assertInstanceOf(SerializationContext::class, $capturedContext);
        self::assertSame(['Default'], $capturedContext->getAttribute('groups'));
        self::assertTrue($capturedContext->getAttribute('max_depth_checks'));
    }

    public function testSerializeAppliesCustomGroups(): void
    {
        $capturedContext = null;
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->expects(self::once())
            ->method('serialize')
            ->willReturnCallback(function ($data, string $format, ?SerializationContext $context = null) use (&$capturedContext): string {
                $capturedContext = $context;

                return '';
            });

        $sut = new Serializer($core);
        $sut->serialize(new \stdClass(), 'json', ['groups' => ['Public', 'Detailed']]);

        self::assertInstanceOf(SerializationContext::class, $capturedContext);
        self::assertSame(['Public', 'Detailed'], $capturedContext->getAttribute('groups'));
    }

    public function testSerializeIgnoresGroupsWhenNotArray(): void
    {
        $capturedContext = null;
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->expects(self::once())
            ->method('serialize')
            ->willReturnCallback(function ($data, string $format, ?SerializationContext $context = null) use (&$capturedContext): string {
                $capturedContext = $context;

                return '';
            });

        $sut = new Serializer($core);
        $sut->serialize(new \stdClass(), 'json', ['groups' => 'Public']);

        self::assertInstanceOf(SerializationContext::class, $capturedContext);
        self::assertSame(['Default'], $capturedContext->getAttribute('groups'));
    }

    public function testToArrayUsesNativeJmsToArray(): void
    {
        $core = SerializerBuilder::create()->build();
        $sut = new Serializer($core);

        $result = $sut->toArray(['hello' => 'world', 'count' => 3]);

        self::assertSame(['hello' => 'world', 'count' => 3], $result);
    }

    public function testToArrayFallsBackToJsonDecodeWhenSerializerNotCoreInstance(): void
    {
        $capturedContext = null;
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->expects(self::once())
            ->method('serialize')
            ->willReturnCallback(function ($data, string $format, ?SerializationContext $context = null) use (&$capturedContext): string {
                $capturedContext = $context;
                self::assertSame('json', $format);

                return '{"id":1,"name":"foo"}';
            });

        $sut = new Serializer($core);
        $result = $sut->toArray(new \stdClass());

        self::assertSame(['id' => 1, 'name' => 'foo'], $result);
        self::assertInstanceOf(SerializationContext::class, $capturedContext);
        self::assertSame(['Default'], $capturedContext->getAttribute('groups'));
        self::assertTrue($capturedContext->getAttribute('max_depth_checks'));
    }

    public function testToArrayFallbackForwardsCustomGroups(): void
    {
        $capturedContext = null;
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->method('serialize')
            ->willReturnCallback(function ($data, string $format, ?SerializationContext $context = null) use (&$capturedContext): string {
                $capturedContext = $context;

                return '{}';
            });

        $sut = new Serializer($core);
        $sut->toArray(new \stdClass(), ['groups' => ['Subresource']]);

        self::assertInstanceOf(SerializationContext::class, $capturedContext);
        self::assertSame(['Subresource'], $capturedContext->getAttribute('groups'));
    }

    public function testToArrayFallbackThrowsOnInvalidJson(): void
    {
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->method('serialize')->willReturn('not-json');

        $sut = new Serializer($core);

        $this->expectException(\JsonException::class);
        $sut->toArray(new \stdClass());
    }

    public function testDeserializeDelegatesAndAppliesContext(): void
    {
        $capturedContext = null;
        $expected = new \stdClass();
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->expects(self::once())
            ->method('deserialize')
            ->willReturnCallback(function (string $data, string $type, string $format, ?DeserializationContext $context = null) use (&$capturedContext, $expected) {
                $capturedContext = $context;
                self::assertSame('{"id":1}', $data);
                self::assertSame(\stdClass::class, $type);
                self::assertSame('json', $format);

                return $expected;
            });

        $sut = new Serializer($core);
        $result = $sut->deserialize('{"id":1}', \stdClass::class, 'json');

        self::assertSame($expected, $result);
        self::assertInstanceOf(DeserializationContext::class, $capturedContext);
        self::assertSame(['Default'], $capturedContext->getAttribute('groups'));
        self::assertTrue($capturedContext->getAttribute('max_depth_checks'));
    }

    public function testDeserializeAppliesCustomGroups(): void
    {
        $capturedContext = null;
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->method('deserialize')
            ->willReturnCallback(function (string $data, string $type, string $format, ?DeserializationContext $context = null) use (&$capturedContext) {
                $capturedContext = $context;

                return null;
            });

        $sut = new Serializer($core);
        $sut->deserialize('{}', \stdClass::class, 'json', ['groups' => ['Internal']]);

        self::assertInstanceOf(DeserializationContext::class, $capturedContext);
        self::assertSame(['Internal'], $capturedContext->getAttribute('groups'));
    }

    public function testDeserializeThrowsOnNonStringData(): void
    {
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->expects(self::never())->method('deserialize');

        $sut = new Serializer($core);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot deserialize data. Expected string, got "array"');

        $sut->deserialize(['not' => 'string'], \stdClass::class, 'json');
    }

    public function testDeserializeThrowsOnNullData(): void
    {
        $core = $this->createMock(CoreSerializerInterface::class);
        $core->expects(self::never())->method('deserialize');

        $sut = new Serializer($core);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot deserialize data. Expected string, got "NULL"');

        $sut->deserialize(null, \stdClass::class, 'json');
    }
}
