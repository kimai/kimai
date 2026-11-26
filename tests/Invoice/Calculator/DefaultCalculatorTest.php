<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\Calculator;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Tag;
use App\Entity\Timesheet;
use App\Invoice\Calculator\AbstractCalculator;
use App\Invoice\Calculator\DefaultCalculator;
use App\Invoice\CalculatorInterface;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use App\Timesheet\RateCalculator\ClassicRateCalculator;
use App\Timesheet\RateCalculator\DecimalRateCalculator;
use App\Timesheet\RateCalculator\RateCalculatorMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(DefaultCalculator::class)]
#[CoversClass(AbstractCalculator::class)]
class DefaultCalculatorTest extends AbstractCalculatorTestCase
{
    protected function getCalculator(): CalculatorInterface
    {
        return new DefaultCalculator();
    }

    public function testWithMultipleEntries(): void
    {
        $date = new \DateTime();
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $timesheet = new Timesheet();
        $timesheet->setDescription('foo 1');
        $timesheet->setBegin(clone $date);
        $timesheet->setDuration(3600);
        $timesheet->setRate(293.27);
        $timesheet->setActivity(new Activity());
        $timesheet->addTag((new Tag())->setName('foo'));
        $timesheet->addTag((new Tag())->setName('bar'));

        $timesheet2 = new Timesheet();
        $timesheet2->setDescription('foo 2');
        $timesheet2->setBegin(new \DateTime('2018-11-18'));
        $timesheet2->setDuration(400);
        $timesheet2->setRate(84);
        $timesheet2->setActivity(new Activity());
        $timesheet2->addTag((new Tag())->setName('bar1'));

        $timesheet3 = new Timesheet();
        $timesheet3->setDescription('foo 3');
        $timesheet3->setBegin(clone $date);
        $timesheet3->setDuration(1800);
        $timesheet3->setRate(111.11);
        $timesheet3->setActivity(new Activity());

        $entries = [$timesheet, $timesheet2, $timesheet3];

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, new InvoiceQuery());
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('default', $sut->getId());
        self::assertEquals(581.17, $sut->getTotal());
        $this->assertTax($sut, 19);
        self::assertEquals('EUR', $model->getCurrency());
        self::assertEquals(488.38, $sut->getSubtotal());
        self::assertEquals(5800, $sut->getTimeWorked());
    }

    /**
     * Each invoice line has to be rounded to the currency precision, so that the sum of all
     * lines equals the subtotal (required for valid e-invoices, see EN 16931 rule BR-CO-10).
     */
    public function testSubtotalIsSumOfRoundedLinesWithClassicRounding(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $entries = [];
        foreach ([27000, 27000, 27000, 1800] as $i => $duration) {
            $timesheet = new Timesheet();
            $timesheet->setBegin(new \DateTime('2026-11-0' . ($i + 1) . ' 08:00:00'));
            $timesheet->setDuration($duration);
            $timesheet->setHourlyRate(103.75);
            $timesheet->setRate(103.75 * $duration / 3600);
            $timesheet->setActivity(new Activity());
            $entries[] = $timesheet;
        }

        $model = (new InvoiceModelFactoryFactory($this))->create(new ClassicRateCalculator())->createModel(new DebugFormatter(), $customer, $template, new InvoiceQuery());
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        $items = $sut->getEntries();
        self::assertCount(4, $items);
        self::assertSame(778.13, $items[0]->getRate());
        self::assertSame(778.13, $items[1]->getRate());
        self::assertSame(778.13, $items[2]->getRate());
        self::assertSame(51.88, $items[3]->getRate());

        $sum = 0.0;
        foreach ($items as $item) {
            $sum += $item->getRate();
        }

        self::assertSame(2386.27, round($sum, 2));
        self::assertSame(2386.27, $sut->getSubtotal());
        self::assertSame(453.39, $sut->getTax());
        self::assertSame(2839.66, $sut->getTotal());

        $rows = $sut->getTaxRows();
        self::assertCount(1, $rows);
        self::assertSame(2386.27, $rows[0]->getBasePrice());
        self::assertSame(453.39, $rows[0]->getAmount());
    }

    public function testRoundsToCustomerCurrency(): void
    {
        $customer = new Customer('foo');
        $customer->setCurrency('JPY');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $entries = [];
        foreach ([1, 2] as $day) {
            $timesheet = new Timesheet();
            $timesheet->setBegin(new \DateTime('2026-11-0' . $day . ' 08:00:00'));
            $timesheet->setDuration(1800);
            $timesheet->setHourlyRate(1234.5);
            $timesheet->setRate(617.25);
            $timesheet->setActivity(new Activity());
            $entries[] = $timesheet;
        }

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, new InvoiceQuery());
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        self::assertEquals('JPY', $model->getCurrency());
        $items = $sut->getEntries();
        self::assertSame(617.0, $items[0]->getRate());
        self::assertSame(617.0, $items[1]->getRate());
        self::assertSame(1234.0, $sut->getSubtotal());
        self::assertSame(234.0, $sut->getTax());
        self::assertSame(1468.0, $sut->getTotal());
    }

    /**
     * Boundary values for both rate calculator modes:
     * - halves (778.125 / 51.875)
     * - floating point noise (103.75 x 0.42h = 43.574999999999996, 67.89 x 3438s = 64.83494999999999)
     * - Classic: rounded twice (4 decimals when stored, then to the currency), so the invoice line
     *   matches the rate of the timesheet (67.89 x 92s = 1.73497 => 1.735 => 1.74)
     *
     * @return iterable<string, array{RateCalculatorMode, array<float>, float, float, float, int}>
     */
    public static function getBoundaryRoundingData(): iterable
    {
        yield 'classic' => [
            new ClassicRateCalculator(),
            [778.13, 51.88, 1.74, 64.84, 43.06],
            939.65,
            178.53,
            1118.18,
            33824,
        ];
        yield 'decimal' => [
            new DecimalRateCalculator(),
            [778.13, 51.88, 2.04, 65.17, 43.58],
            940.80,
            178.75,
            1119.55,
            33876,
        ];
    }

    /**
     * @param array<float> $expectedLines
     */
    #[DataProvider('getBoundaryRoundingData')]
    public function testBoundaryRoundingWithRateCalculatorMode(RateCalculatorMode $mode, array $expectedLines, float $subtotal, float $tax, float $total, int $timeWorked): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $template->setVat(19);

        $records = [
            [103.75, 27000],
            [103.75, 1800],
            [67.89, 92],
            [67.89, 3438],
            [103.75, 1494],
        ];

        $entries = [];
        foreach ($records as $i => [$hourlyRate, $duration]) {
            $timesheet = new Timesheet();
            $timesheet->setBegin(new \DateTime('2026-11-0' . ($i + 1) . ' 08:00:00'));
            $timesheet->setDuration($duration);
            $timesheet->setHourlyRate($hourlyRate);
            // like the RateService would store it
            $timesheet->setRate($mode->calculateRate($hourlyRate, $duration, 'EUR'));
            $timesheet->setActivity(new Activity());
            $entries[] = $timesheet;
        }

        $model = (new InvoiceModelFactoryFactory($this))->create($mode)->createModel(new DebugFormatter(), $customer, $template, new InvoiceQuery());
        $model->addEntries($entries);

        $sut = $this->getCalculator();
        $sut->setModel($model);

        $lines = [];
        $sum = 0.0;
        foreach ($sut->getEntries() as $entry) {
            $lines[] = $entry->getRate();
            $sum += $entry->getRate();
        }

        self::assertSame($expectedLines, $lines);
        // the subtotal must always be the sum of the (rounded) lines
        self::assertSame($subtotal, round($sum, 2));
        self::assertSame($subtotal, $sut->getSubtotal());
        self::assertSame($tax, $sut->getTax());
        self::assertSame($total, $sut->getTotal());
        self::assertSame($timeWorked, $sut->getTimeWorked());

        $rows = $sut->getTaxRows();
        self::assertCount(1, $rows);
        self::assertSame($subtotal, $rows[0]->getBasePrice());
        self::assertSame($tax, $rows[0]->getAmount());
    }
}
