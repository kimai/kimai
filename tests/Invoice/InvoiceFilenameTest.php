<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice;

use App\Entity\Customer;
use App\Entity\InvoiceTemplate;
use App\Entity\Project;
use App\Invoice\InvoiceFilename;
use App\Invoice\NumberGenerator\DateNumberGenerator;
use App\Invoice\NumberGeneratorInterface;
use App\Repository\InvoiceRepository;
use App\Repository\Query\InvoiceQuery;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\InvoiceFilename
 */
class InvoiceFilenameTest extends TestCase
{
    public function testInvoiceFilename(): void
    {
        $customer = new Customer('foo');
        $template = new InvoiceTemplate();
        $query = new InvoiceQuery();
        $project = new Project();
        $project->setName('Demo ProjecT1');

        $query->addProject($project);

        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter(), $customer, $template, $query);
        $model->setNumberGenerator($this->getNumberGeneratorSut());

        $datePrefix = date('ymd');

        $sut = new InvoiceFilename($model);

        self::assertEquals($datePrefix . '-foo-Demo_ProjecT1', $sut->getFilename());
        self::assertEquals($datePrefix . '-foo-Demo_ProjecT1', (string) $sut);

        $customer->setCompany('barß / laölala #   ldksjf 123 MyAwesome GmbH');
        $sut = new InvoiceFilename($model);

        self::assertEquals($datePrefix . '-barss_laolala_ldksjf_123_MyAwesome_GmbH-Demo_ProjecT1', $sut->getFilename());
        self::assertEquals($datePrefix . '-barss_laolala_ldksjf_123_MyAwesome_GmbH-Demo_ProjecT1', (string) $sut);

        $customer->setCompany('까깨꺄꺠꺼께껴꼐꼬꽈sssss');
        $sut = new InvoiceFilename($model);
        self::assertEquals($datePrefix . '-kkakkaekkyakkyaekkeokkekkyeokkyekkokkwasssss-Demo_ProjecT1', $sut->getFilename());

        $customer->setCompany('\"#+ß.!$%&/()=?\\n=/*-+´_<>@' . "\n");
        $sut = new InvoiceFilename($model);
        self::assertEquals($datePrefix . '-ss_n_--Demo_ProjecT1', $sut->getFilename());

        $customer->setCompany('\"#+ß.!$%&/()=?\\n=/*-+´_<>@' . "\n");
        $sut = new InvoiceFilename($model);
        self::assertEquals($datePrefix . '-ss_n_--Demo_ProjecT1', $sut->getFilename());
    }

    private function getNumberGeneratorSut(): NumberGeneratorInterface
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository
            ->expects($this->any())
            ->method('hasInvoice')
            ->willReturn(false);

        return new DateNumberGenerator($repository);
    }
}
