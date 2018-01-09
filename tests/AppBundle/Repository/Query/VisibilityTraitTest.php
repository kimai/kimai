<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiTest\AppBundle\Repository\Query;

use AppBundle\Repository\Query\VisibilityInterface;
use AppBundle\Repository\Query\VisibilityTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AppBundle\Repository\Query\VisibilityTrait
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class VisibilityTraitTest extends TestCase
{
    public function testVisibilityTrait()
    {
        $sut = new VisibilityTraitImplementation();

        $this->assertFalse($sut->isExclusiveVisibility());
        $this->assertEquals(VisibilityTraitImplementation::SHOW_VISIBLE, $sut->getVisibility());

        $sut->setExclusiveVisibility(true);
        $this->assertTrue($sut->isExclusiveVisibility());

        $sut->setVisibility('foo-bar');
        $this->assertEquals(VisibilityTraitImplementation::SHOW_VISIBLE, $sut->getVisibility());

        $sut->setVisibility(VisibilityTraitImplementation::SHOW_BOTH);
        $this->assertEquals(VisibilityTraitImplementation::SHOW_BOTH, $sut->getVisibility());

        $sut->setVisibility(VisibilityTraitImplementation::SHOW_HIDDEN);
        $this->assertEquals(VisibilityTraitImplementation::SHOW_HIDDEN, $sut->getVisibility());

        $sut->setVisibility(VisibilityTraitImplementation::SHOW_VISIBLE);
        $this->assertEquals(VisibilityTraitImplementation::SHOW_VISIBLE, $sut->getVisibility());
    }
}
