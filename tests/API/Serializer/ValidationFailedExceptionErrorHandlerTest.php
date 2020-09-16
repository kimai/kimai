<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Serializer;

use App\API\Serializer\ValidationFailedExceptionErrorHandler;
use App\Validator\ValidationFailedException;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\API\Serializer\ValidationFailedExceptionErrorHandler
 */
class ValidationFailedExceptionErrorHandlerTest extends TestCase
{
    public function testSubscribingMethods()
    {
        self::assertEquals([[
            'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'type' => 'App\Validator\ValidationFailedException',
            'format' => 'json',
            'method' => 'serializeExceptionToJson',
        ]], ValidationFailedExceptionErrorHandler::getSubscribingMethods());
    }

    public function testWithEmptyConstraintsList()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $sut = new ValidationFailedExceptionErrorHandler($translator);

        $constraints = new ConstraintViolationList();
        $validations = new ValidationFailedException($constraints, 'Uuups, that is broken');

        $serialization = $this->createMock(SerializationVisitorInterface::class);
        $expected = [
            'code' => '400',
            'message' => null,
            'errors' => [
                'children' => []
            ]
        ];
        self::assertEquals($expected, $sut->serializeExceptionToJson($serialization, $validations, []));
    }

    public function testWithConstraintsList()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $sut = new ValidationFailedExceptionErrorHandler($translator);
        $translator->method('trans')->willReturnArgument(0);

        $constraints = new ConstraintViolationList();
        $constraints->add(new ConstraintViolation('toooo many tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));
        $constraints->add(new ConstraintViolation('missing tests', 'abc.def', [], '$root', 'begin', 4, null, null, null, '$cause'));
        $constraints->add(new ConstraintViolation('missing tests', 'test %wuuf% 123', ['%wuuf%' => 'xcv'], '$root', 'end', 4, null, null, null, '$cause'));
        $validations = new ValidationFailedException($constraints, 'Uuups, that is broken');

        $serialization = $this->createMock(SerializationVisitorInterface::class);
        $expected = [
            'code' => '400',
            'message' => 'Uuups, that is broken',
            'errors' => [
                'children' => [
                    'begin' => [
                        'errors' => [
                            0 => 'abc.def',
                            1 => 'abc.def',
                        ],
                    ],
                    'end' => [
                        'errors' => [
                            0 => 'test %wuuf% 123',
                        ],
                    ]
                ]
            ]
        ];
        self::assertEquals($expected, $sut->serializeExceptionToJson($serialization, $validations, []));
    }
}
