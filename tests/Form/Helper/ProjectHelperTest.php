<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Helper;

use App\Configuration\LocaleService;
use App\Entity\Customer;
use App\Entity\Project;
use App\Form\Helper\ProjectHelper;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @covers \App\Form\Helper\ProjectHelper
 */
class ProjectHelperTest extends TestCase
{
    private function createSut(string $format): ProjectHelper
    {
        $config = SystemConfigurationFactory::createStub(['project.choice_pattern' => $format]);

        $localeService = new LocaleService(['en_US' => ['date' => 'dd.MM.y']]);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturn('dating');
        $helper = new ProjectHelper($config, $localeService, $translator);
        $helper->setLocale('en_US');

        return $helper;
    }

    public function testInvalidPattern(): void
    {
        $helper = $this->createSut('sdfsdf');
        self::assertEquals(ProjectHelper::PATTERN_NAME, $helper->getChoicePattern());
    }

    public function testGetChoicePattern(): void
    {
        $helper = $this->createSut(
            ProjectHelper::PATTERN_NAME . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_COMMENT . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_CUSTOMER . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_ORDERNUMBER . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_START . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_END . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_DATERANGE
        );

        self::assertEquals(
            ProjectHelper::PATTERN_NAME . ProjectHelper::SPACER .
            ProjectHelper::PATTERN_COMMENT . ProjectHelper::SPACER .
            ProjectHelper::PATTERN_CUSTOMER . ProjectHelper::SPACER .
            ProjectHelper::PATTERN_ORDERNUMBER . ProjectHelper::SPACER .
            ProjectHelper::PATTERN_START . ProjectHelper::SPACER .
            ProjectHelper::PATTERN_END . ProjectHelper::SPACER .
            ProjectHelper::PATTERN_START . '-' . ProjectHelper::PATTERN_END,
            $helper->getChoicePattern()
        );
    }

    public function testGetChoiceLabel(): void
    {
        $helper = $this->createSut(
            ProjectHelper::PATTERN_NAME . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_COMMENT . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_CUSTOMER . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_ORDERNUMBER
        );

        $project = new Project();
        $project->setName(' - --- - -FOO BAR- --- -  -  - ');
        $customer = new Customer(' - --- - - Acme company- --- -  -  - ');
        $project->setCustomer($customer);
        self::assertEquals('--- - -FOO BAR- --- -  -  -  -  -  - --- - - Acme company- ---', $helper->getChoiceLabel($project));

        $project = new Project();
        $project->setName('FOO BAR');
        $customer = new Customer('Acme company');
        $project->setCustomer($customer);
        $project->setComment('Lorem Ipsum');
        $project->setOrderNumber('F76/123');
        self::assertEquals('FOO BAR - Lorem Ipsum - Acme company - F76/123', $helper->getChoiceLabel($project));
    }

    public function testGetChoiceLabelWithDates(): void
    {
        $helper = $this->createSut(
            ProjectHelper::PATTERN_NAME . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_START . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_END . ProjectHelper::PATTERN_SPACER .
            ProjectHelper::PATTERN_DATERANGE
        );

        $project = new Project();
        $project->setName('FOO BAR');
        self::assertEquals('FOO BAR -  -  - -', $helper->getChoiceLabel($project));
        $project->setStart(new \DateTime('2018-12-27 18:45:12'));
        self::assertEquals('FOO BAR - dating: 27.12.2018 -  - dating: 27.12.2018-', $helper->getChoiceLabel($project));
        $project->setEnd(new \DateTime('2019-02-14 01:23:45'));
        self::assertEquals('FOO BAR - dating: 27.12.2018 - dating: 14.02.2019 - dating: 27.12.2018-dating: 14.02.2019', $helper->getChoiceLabel($project));
    }
}
