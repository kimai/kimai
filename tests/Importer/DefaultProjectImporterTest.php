<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Importer;

use App\Customer\CustomerService;
use App\Entity\Customer;
use App\Entity\Project;
use App\Importer\DefaultProjectImporter;
use App\Importer\UnsupportedFormatException;
use App\Project\ProjectService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\DefaultProjectImporter
 */
class DefaultProjectImporterTest extends TestCase
{
    private function getSut(int $count = 1): DefaultProjectImporter
    {
        $projectService = $this->createMock(ProjectService::class);
        $projectService->expects($this->exactly($count))->method('createNewProject')->willReturnCallback(
            function (Customer $customer) {
                $customer->setTimezone('Europe/Paris');
                $project = new Project();
                $project->setCustomer($customer);

                return $project;
            }
        );
        $customerService = $this->createMock(CustomerService::class);
        $customerService->expects($this->once())->method('createNewCustomer')->willReturn(new Customer());

        $sut = new DefaultProjectImporter($projectService, $customerService);

        return $sut;
    }

    private function getDefaultImport(): array
    {
        return [
            'name' => 'Test project',
        ];
    }

    private function prepareProject(array $values = []): Project
    {
        $sut = $this->getSut();

        $import = array_merge($this->getDefaultImport(), $values);

        return $sut->convertEntryToProject($import);
    }

    public function testImportMissingName()
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('Missing project name, expected in one of the columns: "Name", "Project , "Project Name", "Project-Name", "ProjectName"');

        return $this->getSut(0)->convertEntryToProject(['CuStOMer-name' => 'Test CUSTOMER!']);
    }

    public function testImport()
    {
        $project = $this->prepareProject(['CuStOMer-name' => 'Test CUSTOMER!']);
        self::assertEquals('Test project', $project->getName());
        self::assertEquals('Test CUSTOMER!', $project->getCustomer()->getName());
    }

    public function testImportWithMultipleValues()
    {
        $project = $this->prepareProject([
            'CuStOMer-name' => 'Test CUSTOMER!',
            'order date' => '2020-07-21 17:28:54',
            'budget' => 1000.17,
            'time budget' => 3600,
            'meta.abcd' => 'uztiuzgubhöklji7gl',
        ]);
        self::assertEquals('Test project', $project->getName());
        self::assertEquals('Test CUSTOMER!', $project->getCustomer()->getName());
        self::assertEquals(1000.17, $project->getBudget());
        self::assertEquals(3600, $project->getTimeBudget());
        self::assertEquals('2020-07-21T17:28:54+0200', $project->getOrderDate()->format(DATE_ISO8601));
        self::assertEquals('uztiuzgubhöklji7gl', $project->getMetaField('abcd')->getValue());
    }
}
