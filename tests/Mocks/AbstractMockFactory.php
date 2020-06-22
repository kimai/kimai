<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase;

abstract class AbstractMockFactory
{
    /**
     * @var TestCase
     */
    private $testCase;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    protected function getTestCase(): TestCase
    {
        return $this->testCase;
    }

    protected function getMockBuilder(string $className): MockBuilder
    {
        return new MockBuilder($this->testCase, $className);
    }
}
