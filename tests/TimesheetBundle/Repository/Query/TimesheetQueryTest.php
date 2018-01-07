<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 07.01.18
 * Time: 11:12
 */

namespace KimaiTest\TimesheetBundle\Repository\Query;

use AppBundle\Repository\Query\BaseQuery;
use \PHPUnit\Framework\TestCase;

class TimesheetQueryTest extends TestCase
{
    public function testBaseQueryHasOverwrittenFields()
    {
        $class = new \ReflectionClass(new BaseQuery());
        $this->assertTrue($class->hasProperty('order'));
        $this->assertTrue($class->hasProperty('orderBy'));
    }

    public function testGetUser()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testSetUser()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testGetActivity()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testSetActivity()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testGetProject()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testSetProject()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testGetCustomer()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testSetCustomer()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testGetState()
    {
        $this->markTestIncomplete(__METHOD__);
    }

    public function testSetState()
    {
        $this->markTestIncomplete(__METHOD__);
    }
}
