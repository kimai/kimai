<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Utils;

use App\Utils\CommandStyle;
use App\Validator\ValidationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @covers \App\Utils\CommandStyle
 */
class CommandStyleTest extends TestCase
{
    public function testStyles()
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $sut = new CommandStyle($input, $output);
        $sut->error('FooBar-Error');
        $sut->warning('Hello-Warning');
        $sut->success('World-Success');

        $exception = new ValidationFailedException(new ConstraintViolationList([
            new ConstraintViolation('123', null, [], $this, null, 123),
            new ConstraintViolation('456', null, [], $this, null, 456),
        ]));
        $sut->validationError($exception);

        $result = $output->fetch();
        self::assertStringContainsString('[ERROR] FooBar-Error', $result);
        self::assertStringContainsString('[WARNING] Hello-Warning', $result);
        self::assertStringContainsString('[OK] World-Success', $result);
        self::assertStringContainsString('[ERROR]  (123)', $result);
        self::assertStringContainsString('[ERROR]  (456)', $result);
    }
}
