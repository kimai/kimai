<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export;

use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\User;
use App\Export\ExportFilename;
use App\Repository\Query\TimesheetQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Export\ExportFilename
 */
class ExportFilenameTest extends TestCase
{
    public function testExportFilename()
    {
        $datePrefix = date('Ymd');

        $query = new TimesheetQuery();

        $sut = new ExportFilename($query);

        self::assertEquals($datePrefix . '-kimai-export', $sut->getFilename());
        self::assertEquals($datePrefix . '-kimai-export', (string) $sut);

        $customer = new Customer('foo');
        $query = new TimesheetQuery();
        $query->addCustomer($customer);
        $sut = new ExportFilename($query);

        self::assertEquals($datePrefix . '-foo', $sut->getFilename());
        self::assertEquals($datePrefix . '-foo', (string) $sut);

        $customer->setCompany('barß / laölala #   ldksjf 123 MyAwesome GmbH');
        $sut = new ExportFilename($query);

        self::assertEquals($datePrefix . '-barss_laolala_ldksjf_123_MyAwesome_GmbH', $sut->getFilename());
        self::assertEquals($datePrefix . '-barss_laolala_ldksjf_123_MyAwesome_GmbH', (string) $sut);

        $customer->setCompany('까깨꺄꺠꺼께껴꼐꼬꽈sssss');
        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-kkakkaekkyakkyaekkeokkekkyeokkyekkokkwasssss', $sut->getFilename());

        $customer->setCompany('\"#+ß.!$%&/()=?\\n=/*-+´_<>@' . "\n");
        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-ss_n_-', $sut->getFilename());

        $project = new Project();
        $project->setName('Demo ProjecT1');
        $customer->setCompany('\"#+ß.!$%&/()=?\\n=/*-+´_<>@' . "\n");
        $query->addProject($project);

        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-ss_n_--Demo_ProjecT1', $sut->getFilename());

        $user = new User();
        $user->setUserIdentifier('ayumi');
        $query->addUser($user);

        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-ss_n_--Demo_ProjecT1-ayumi', $sut->getFilename());
        $user->setAlias('Martin Müller-Lüdenscheidt');

        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-ss_n_--Demo_ProjecT1-Martin_Muller-Ludenscheidt', $sut->getFilename());

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $query->addUser($user);
        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-ss_n_--Demo_ProjecT1', $sut->getFilename());

        $project = new Project();
        $project->setName('Project2');
        $query->addProject($project);
        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-ss_n_-', $sut->getFilename());

        $customer = new Customer('Customer AAAA');
        $query->addCustomer($customer);

        $sut = new ExportFilename($query);
        self::assertEquals($datePrefix . '-kimai-export', $sut->getFilename());
    }
}
