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

use AppBundle\Repository\Query\UserQuery;
use AppBundle\Repository\Query\VisibilityInterface;
use AppBundle\Repository\Query\VisibilityTrait;

/**
 * @covers \AppBundle\Repository\Query\UserQuery
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserQueryTest extends BaseQueryTest
{
    public function testQuery()
    {
        $sut = new UserQuery();
        $this->assertBaseQuery($sut);
        $this->assertInstanceOf(VisibilityInterface::class, $sut);
        $this->assertArrayHasKey(VisibilityTrait::class, class_uses($sut));
        $this->assertRole($sut);
    }

    protected function assertRole(UserQuery $sut)
    {
        $this->assertNull($sut->getRole());

        $sut->setRole('foo-bar');
        $this->assertNull($sut->getRole());

        $sut->setRole('ROLE_USER');
        $this->assertEquals('ROLE_USER', $sut->getRole());
    }
}
