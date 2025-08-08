<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\IcalLink;
use App\Validator\Constraints\IcalLinkValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @covers \App\Validator\Constraints\IcalLinkValidator
 */
class IcalLinkValidatorTest extends TestCase
{
    private IcalLinkValidator $validator;
    private IcalLink $constraint;
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        $this->validator = new IcalLinkValidator();
        $this->constraint = new IcalLink();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testValidIcalLinks(): void
    {
        $validLinks = [
            'https://example.com/calendar.ics',
            'http://example.com/calendar.ics',
            'https://example.com/path/to/calendar.ics',
            'https://example.com/calendar.ICS',
            'https://example.com/calendar.Ics',
        ];

        $this->context->expects($this->never())
            ->method('buildViolation');

        foreach ($validLinks as $link) {
            $this->validator->validate($link, $this->constraint);
        }
    }

    public function testInvalidUrls(): void
    {
        $invalidUrls = [
            'not-a-url',
            'ftp://example.com/calendar.ics',
            'invalid-protocol://example.com/calendar.ics',
        ];

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(IcalLink::INVALID_URL)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->invalidUrlMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate('not-a-url', $this->constraint);
    }

    public function testUrlsWithoutIcsExtension(): void
    {
        $invalidUrls = [
            'https://example.com/calendar',
            'https://example.com/calendar.txt',
            'https://example.com/calendar.pdf',
            'https://example.com/calendar.ics.txt',
        ];

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())
            ->method('setCode')
            ->with(IcalLink::INVALID_EXTENSION)
            ->willReturnSelf();
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->invalidExtensionMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate('https://example.com/calendar', $this->constraint);
    }

    public function testEmptyValues(): void
    {
        $emptyValues = [null, ''];

        $this->context->expects($this->never())
            ->method('buildViolation');

        foreach ($emptyValues as $value) {
            $this->validator->validate($value, $this->constraint);
        }
    }

    public function testNonStringValues(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedValueException::class);

        $this->validator->validate(123, $this->constraint);
    }
} 