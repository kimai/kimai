<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Doctrine;

use App\Doctrine\SqliteSessionInitSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Doctrine\SqliteSessionInitSubscriber
 */
class SqliteSessionInitSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents()
    {
        $sut = new SqliteSessionInitSubscriber();
        $events = $sut->getSubscribedEvents();
        $this->assertTrue(\in_array(Events::postConnect, $events));
    }

    public function testPostConnectWithSqlite()
    {
        $sut = new SqliteSessionInitSubscriber();

        $platformMock = $this->getMockBuilder(SqlitePlatform::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $platformMock->expects($this->once())->method('getName')->willReturn('sqlite');

        $connectionMock = $this->createMock(Connection::class);

        $connectionMock->expects($this->once())->method('getDatabasePlatform')->willReturn($platformMock);
        $connectionMock->expects($this->once())->method('exec')->with('PRAGMA foreign_keys = ON;');

        $args = new ConnectionEventArgs($connectionMock);
        $sut->postConnect($args);
    }

    public function testPostConnectWithMysql()
    {
        $sut = new SqliteSessionInitSubscriber();

        $platformMock = $this->getMockBuilder(MySqlPlatform::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $platformMock->expects($this->once())->method('getName')->willReturn('mysql');

        $connectionMock = $this->createMock(Connection::class);

        $connectionMock->expects($this->once())->method('getDatabasePlatform')->willReturn($platformMock);
        $connectionMock->expects($this->never())->method('exec')->with('PRAGMA foreign_keys = ON;');

        $args = new ConnectionEventArgs($connectionMock);
        $sut->postConnect($args);
    }
}
