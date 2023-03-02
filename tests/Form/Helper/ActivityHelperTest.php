<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Helper;

use App\Entity\Activity;
use App\Form\Helper\ActivityHelper;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Form\Helper\ActivityHelper
 */
class ActivityHelperTest extends TestCase
{
    private function createSut(string $format): ActivityHelper
    {
        $config = SystemConfigurationFactory::createStub(['activity.choice_pattern' => $format]);
        $helper = new ActivityHelper($config);

        return $helper;
    }

    public function testInvalidPattern(): void
    {
        $helper = $this->createSut('sdfsdf');
        self::assertEquals(ActivityHelper::PATTERN_NAME, $helper->getChoicePattern());
    }

    public function testGetChoicePattern(): void
    {
        $helper = $this->createSut(
            ActivityHelper::PATTERN_NAME . ActivityHelper::PATTERN_SPACER .
            ActivityHelper::PATTERN_COMMENT
        );

        self::assertEquals(
            ActivityHelper::PATTERN_NAME . ActivityHelper::SPACER .
            ActivityHelper::PATTERN_COMMENT,
            $helper->getChoicePattern()
        );
    }

    public function testGetChoiceLabel(): void
    {
        $helper = $this->createSut(ActivityHelper::PATTERN_NAME . ActivityHelper::PATTERN_SPACER . ActivityHelper::PATTERN_COMMENT);

        $activity = new Activity();
        $activity->setName(' - --- - -FOO BAR- --- -  -  - ');
        self::assertEquals('--- - -FOO BAR- ---', $helper->getChoiceLabel($activity));

        $activity->setName('FOO BAR');
        $activity->setComment('Lorem Ipsum');
        self::assertEquals('FOO BAR - Lorem Ipsum', $helper->getChoiceLabel($activity));
    }
}
