<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Entity;

use App\Entity\Project;

/**
 * @covers \App\Entity\Project
 */
class ProjectTest extends AbstractEntityTest
{
    public function testDefaultValues()
    {
        $sut = new Project();
        $this->assertNull($sut->getId());
        $this->assertNull($sut->getCustomer());
        $this->assertNull($sut->getName());
        $this->assertNull($sut->getOrderNumber());
        $this->assertNull($sut->getComment());
        $this->assertTrue($sut->getVisible());
        $this->assertEquals(0.0, $sut->getBudget());
        // activities
        $this->assertNull($sut->getFixedRate());
        $this->assertNull($sut->getHourlyRate());

        $this->assertInstanceOf(Project::class, $sut->setFixedRate(13.47));
        $this->assertEquals(13.47, $sut->getFixedRate());
        $this->assertInstanceOf(Project::class, $sut->setHourlyRate(99));
        $this->assertEquals(99, $sut->getHourlyRate());
    }
}
