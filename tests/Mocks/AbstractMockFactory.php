<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractMockFactory
{
    public function __construct(private readonly TestCase $testCase)
    {
    }

    protected function getTestCase(): TestCase
    {
        return $this->testCase;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T&MockObject
     */
    protected function createMock(string $className): object
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock(); // @phpstan-ignore return.type
    }

    /**
     * @param class-string $className
     */
    protected function getMockBuilder(string $className): MockBuilder
    {
        return new MockBuilder($this->testCase, $className);
    }
}
