<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet\RateCalculator;

use App\Tests\Mocks\SystemConfigurationFactory;
use App\Timesheet\RateCalculator\ClassicRateCalculator;
use App\Timesheet\RateCalculator\DecimalRateCalculator;
use App\Timesheet\RateCalculator\RateCalculatorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RateCalculatorFactory::class)]
class RateCalculatorFactoryTest extends TestCase
{
    private function assertCreatesClassicConfig(array $config): void
    {
        $config = SystemConfigurationFactory::createStub($config);
        $sut = new RateCalculatorFactory($config);

        $mode = $sut->getRateCalculatorMode();
        self::assertInstanceOf(ClassicRateCalculator::class, $mode);
    }

    public function testCreatesClassic(): void
    {
        $this->assertCreatesClassicConfig([]);
        $this->assertCreatesClassicConfig(['invoice' => ['rounding_mode' => 'classic']]);
        $this->assertCreatesClassicConfig(['invoice' => ['rounding_mode' => '']]);
        $this->assertCreatesClassicConfig(['invoice' => ['rounding_mode' => 'foo']]);
        $this->assertCreatesClassicConfig(['invoice' => ['rounding_mode' => 'DECIMAL']]);
    }

    public function testCreateWithDecimalConfig(): void
    {
        $config = SystemConfigurationFactory::createStub(['invoice' => ['rounding_mode' => 'decimal']]);
        $sut = new RateCalculatorFactory($config);

        $mode = $sut->getRateCalculatorMode();
        self::assertInstanceOf(DecimalRateCalculator::class, $mode);
    }
}
