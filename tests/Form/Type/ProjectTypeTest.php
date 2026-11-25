<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Configuration\LocaleService;
use App\Form\Helper\CustomerHelper;
use App\Form\Helper\ProjectHelper;
use App\Form\Type\ProjectType;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ProjectType::class)]
class ProjectTypeTest extends TestCase
{
    private function createSut(): ProjectType
    {
        $config = SystemConfigurationFactory::createStub([]);
        $localeService = new LocaleService(['en_US' => LocaleService::DEFAULT_SETTINGS]);
        $translator = $this->createMock(TranslatorInterface::class);

        return new ProjectType(new ProjectHelper($config, $localeService, $translator), new CustomerHelper($config));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, string|int>}>
     */
    public static function getDateOptions(): iterable
    {
        $today = (new \DateTime())->format('Y-m-d');

        // without dates the API uses today
        yield 'without dates' => [[], []];
        yield 'ignored dates' => [['ignore_date' => true, 'project_date_start' => new \DateTime('2020-05-17')], ['ignoreDates' => 1]];
        yield 'both dates' => [['project_date_start' => new \DateTime('2020-05-17'), 'project_date_end' => new \DateTime('2020-05-23')], ['start' => '2020-05-17', 'end' => '2020-05-23']];
        yield 'start only' => [['project_date_start' => new \DateTime('2020-05-17')], ['start' => '2020-05-17', 'end' => $today]];
        yield 'end only' => [['project_date_end' => new \DateTime('2020-05-23')], ['start' => $today, 'end' => '2020-05-23']];
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, string|int> $dateParams
     */
    #[DataProvider('getDateOptions')]
    public function testGetLazyLoadApi(array $options, array $dateParams): void
    {
        $options = array_merge(['ignore_date' => false, 'project_date_start' => null, 'project_date_end' => null, 'customers' => 3], $options);
        $api = $this->createSut()->getLazyLoadApi($options);

        self::assertEquals([
            'route' => 'get_projects',
            'params' => array_merge(['visible' => 1, 'orderBy' => 'customer', 'searchCustomer' => 1], $dateParams),
            'parent_param' => 'customer',
            'parent' => 3,
            'all_without_parent' => true,
        ], $api);
    }
}
