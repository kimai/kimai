<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Configuration\LocaleService;
use App\Form\Helper\ActivityHelper;
use App\Form\Helper\ProjectHelper;
use App\Form\Type\ActivityType;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ActivityType::class)]
class ActivityTypeTest extends TestCase
{
    private function createSut(): ActivityType
    {
        $config = SystemConfigurationFactory::createStub([]);
        $localeService = new LocaleService(['en_US' => LocaleService::DEFAULT_SETTINGS]);
        $translator = $this->createMock(TranslatorInterface::class);

        return new ActivityType(new ActivityHelper($config), new ProjectHelper($config, $localeService, $translator));
    }

    /**
     * @return iterable<string, array{mixed, array<string, string>, mixed}>
     */
    public static function getProjects(): iterable
    {
        yield 'null' => [null, ['globals' => 'true'], null];
        yield 'empty' => [[], ['globals' => 'true'], []];
        // quick entry row without project
        yield 'null in array' => [[null], ['globals' => 'true'], []];
        yield 'project' => [[7], [], [7]];
    }

    /**
     * @param array<string, string> $params
     */
    #[DataProvider('getProjects')]
    public function testGetLazyLoadApi(mixed $projects, array $params, mixed $parent): void
    {
        self::assertEquals([
            'route' => 'get_activities',
            'params' => array_merge(['visible' => 1, 'orderBy' => 'project', 'order' => 'DESC', 'searchProject' => 1], $params),
            'parent_param' => 'project',
            'parent' => $parent,
            'all_without_parent' => false,
        ], $this->createSut()->getLazyLoadApi(['projects' => $projects]));
    }
}
