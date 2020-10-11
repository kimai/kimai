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
use App\Project\ProjectService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Importer\DefaultProjectImporter
 */
class DefaultProjectImporterTest extends TestCase
{
    private function getSut(): DefaultProjectImporter
    {
        $projectService = $this->createMock(ProjectService::class);
        $projectService->expects($this->once())->method('createNewProject')->willReturnCallback(
            function ($customer) {
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

    public function testImport()
    {
        $project = $this->prepareProject(['CuStOMer-name' => 'Test CUSTOMER!']);
        self::assertEquals('Test project', $project->getName());
        self::assertEquals('Test CUSTOMER!', $project->getCustomer()->getName());
    }

    public function testImport2()
    {
        $project = $this->prepareProject(['CuStOMer-name' => 'Test CUSTOMER!']);
        self::assertEquals('Test project', $project->getName());
        self::assertEquals('Test CUSTOMER!', $project->getCustomer()->getName());
    }
}
