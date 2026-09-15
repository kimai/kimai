<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Base;

use App\Activity\ActivityStatisticService;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Export\Base\HtmlRenderer;
use App\Project\ProjectStatisticService;
use App\Repository\Query\TimesheetQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Reproduces BUG-6039: an export document's printed total must equal the sum
 * of its own printed rows, for every rounded decimal duration and money column.
 */
#[CoversClass(HtmlRenderer::class)]
#[Group('integration')]
class PrintDecimalReconciliationTest extends KernelTestCase
{
    use TimesheetEntryFixtureTrait;

    private function getRenderer(): HtmlRenderer
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        return new HtmlRenderer(
            $twig,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ProjectStatisticService::class),
            $this->createMock(ActivityStatisticService::class),
            'html',
            'print',
            'export/print.html.twig'
        );
    }

    private function renderDecimal(array $entries, bool $decimal = true): string
    {
        $renderer = $this->getRenderer();

        $currentUser = new User();
        $currentUser->setPreferenceValue('export_decimal', $decimal);
        $currentUser->setUserIdentifier('foo-bar');

        $query = new TimesheetQuery();
        $query->setCurrentUser($currentUser);

        $response = $renderer->render($entries, $query);
        $content = $response->getContent();
        self::assertIsString($content);

        return $content;
    }

    public function testThreeTenMinuteEntriesReconcileToPointFiveOne(): void
    {
        $content = $this->renderDecimal($this->createThreeTenMinuteEntries());

        // each row prints 0.17, and the sum of the printed rows is 0.51 - not the 0.50
        // that round(1800/3600, 2) would produce from the raw second sum
        self::assertSame(3, substr_count($content, 'data-duration-decimal="0.17"'));
        self::assertStringContainsString('data-duration-decimal="0.51"', $content);
        self::assertStringNotContainsString('data-duration-decimal="0.50"', $content);
    }

    public function testMoneyTotalReconcilesToRoundedRowSum(): void
    {
        $content = $this->renderDecimal($this->createThreeTenMinuteEntries());

        // three rows of EUR 1.6666667 print as 1.67 each; their total must be 5.01, matching
        // the sum of the printed rows, not 5.00 from the unrounded raw sum
        self::assertGreaterThanOrEqual(3, substr_count($content, '1.67'));
        self::assertStringContainsString('5.01', $content);
        self::assertStringNotContainsString('5.00<', $content);
    }

    public function testInternalRateTotalReconcilesToRoundedRowSum(): void
    {
        $content = $this->renderDecimal($this->createThreeTenMinuteEntries());

        // the internalRate column uses the same rate value here, so it must reconcile
        // to 5.01 exactly like the rate column
        self::assertGreaterThanOrEqual(3, substr_count($content, '1.67'));
        self::assertStringContainsString('5.01', $content);
    }

    public function testMoneyReconciliationHoldsAtHalfCentBoundary(): void
    {
        // a flat entry rate of 2.505 is an exact half-cent. ICU's default CURRENCY
        // rounding (half-even) would print each row as 2.50, but the reconciled total
        // goes through moneyValue()'s PHP round() (half-away-from-zero, 2.505 -> 2.51) -
        // if money() and moneyValue() disagree on that rounding, three printed 2.50 rows
        // sum to a printed total of 7.53 instead of 7.50, reintroducing the exact defect
        // this bug fixed.
        $content = $this->renderDecimal($this->createEntries(3, 600, 2.505));

        // rate and internalRate columns both show 2.51 per row (3 rows x 2 columns)
        self::assertGreaterThanOrEqual(3, substr_count($content, '2.51'));
        self::assertStringContainsString('7.53', $content);
        self::assertStringNotContainsString('2.50', $content);
        self::assertStringNotContainsString('7.50', $content);
    }

    public function testNoResidueWhenRowsHaveNoRoundingRemainder(): void
    {
        // three exact 1800-second (0.50h) entries have no rounding residue at all,
        // so the total must simply be their exact sum
        $content = $this->renderDecimal($this->createEntries(3, 1800, 5.0));

        self::assertSame(3, substr_count($content, 'data-duration-decimal="0.50"'));
        self::assertStringContainsString('data-duration-decimal="1.50"', $content);
    }

    public function testRoundDownDirectionTenEntriesOf1740Seconds(): void
    {
        // 1740/3600 = 0.48333... rounds down to 0.48 per row; the raw-sum total would be
        // round(17400/3600, 2) == 4.83, larger than the sum of the ten printed 0.48 rows (4.80)
        $content = $this->renderDecimal($this->createEntries(10, 1740, 8.0));

        self::assertSame(10, substr_count($content, 'data-duration-decimal="0.48"'));
        self::assertStringContainsString('data-duration-decimal="4.80"', $content);
        self::assertStringNotContainsString('data-duration-decimal="4.83"', $content);
    }

    public function testNoDriftWithSizeHundredEntries(): void
    {
        // each row is 600 seconds -> 0.17h; the reconciled total for 100 rows must be
        // exactly 100 * 0.17 == 17.00, not round(100 * 600 / 3600, 2) == 16.67
        $content = $this->renderDecimal($this->createEntries(100, 600, 1.6666667));

        self::assertStringContainsString('data-duration-decimal="17.00"', $content);
    }

    public function testMultiLevelGroupTotalsReconcileAndGrandTotalIsSumOfGroupTotals(): void
    {
        $customerIdProperty = new \ReflectionProperty(Customer::class, 'id');
        $projectIdProperty = new \ReflectionProperty(Project::class, 'id');

        $customerA = new Customer('Customer A');
        $customerIdProperty->setValue($customerA, 1);
        $projectA = new Project();
        $projectA->setName('project a');
        $projectA->setCustomer($customerA);
        $projectIdProperty->setValue($projectA, 1);

        $customerB = new Customer('Customer B');
        $customerIdProperty->setValue($customerB, 2);
        $projectB = new Project();
        $projectB->setName('project b');
        $projectB->setCustomer($customerB);
        $projectIdProperty->setValue($projectB, 2);

        $entries = array_merge(
            $this->createEntries(3, 600, 1.6666667, $projectA),
            $this->createEntries(3, 600, 1.6666667, $projectB)
        );

        $content = $this->renderDecimal($entries);

        // each customer group totals 0.51 (three 0.17 rows), printed in both the per-project
        // and per-activity summary tables; the grand total across both groups is their sum, 1.02
        self::assertGreaterThanOrEqual(2, substr_count($content, 'data-duration-decimal="0.51"'));
        self::assertStringContainsString('data-duration-decimal="1.02"', $content);
    }

    public function testDecimalOffPrintsExactHourMinuteValuesWithNoRoundingCompensation(): void
    {
        $content = $this->renderDecimal($this->createThreeTenMinuteEntries(), false);

        // h:mm rendering has no rounding residue to reconcile: three 0:10 rows total 0:30
        self::assertSame(3, substr_count($content, 'data-duration="0:10"'));
        self::assertStringContainsString('data-duration="0:30"', $content);
        self::assertStringNotContainsString('data-duration="0:11"', $content);
        self::assertStringNotContainsString('data-duration="0:31"', $content);
    }

    public function testTogglingDecimalDisplayIsLosslessBecauseBothValuesAreAlwaysRendered(): void
    {
        // the decimal toggle only swaps which pre-rendered attribute the browser shows;
        // both the exact h:mm and the reconciled decimal value are always present in the
        // same markup, regardless of the decimal flag used to render the document
        $content = $this->renderDecimal($this->createThreeTenMinuteEntries());

        self::assertStringContainsString('data-duration-decimal="0.17" data-duration="0:10"', $content);
        self::assertStringContainsString('data-duration-decimal="0.51" data-duration="0:30"', $content);
    }
}
