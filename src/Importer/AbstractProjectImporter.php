<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use App\Customer\CustomerService;
use App\Entity\Customer;
use App\Entity\Project;
use App\Project\ProjectService;

abstract class AbstractProjectImporter implements ProjectImporterInterface
{
    private $projectService;
    private $customerService;
    /**
     * @var Customer[]
     */
    private $customerCache = [];

    public function __construct(ProjectService $projectService, CustomerService $customerService)
    {
        $this->projectService = $projectService;
        $this->customerService = $customerService;
    }

    protected function findProjectByName(string $name): ?Project
    {
        return $this->projectService->findProjectByName($name);
    }

    protected function findCustomerByName(string $name): ?Customer
    {
        return $this->customerService->findCustomerByName($name);
    }

    public function convertEntryToProject(array $entry, array $options = []): Project
    {
        $project = $this->findProject($entry);

        $this->convertEntry($project, $entry, $options);

        return $project;
    }

    protected function createNewProject(Customer $customer, string $name): Project
    {
        $project = $this->projectService->createNewProject($customer);
        $project->setName($name);

        return $project;
    }

    protected function findProject(array $entry): ?Project
    {
        $name = $this->findCustomerName($entry);
        $customer = $this->findCustomer($name);

        $name = $this->findProjectName($entry);
        $project = $this->findProjectByName($name);

        if ($project === null) {
            $project = $this->createNewProject($customer, $name);
        }

        if ($customer->getId() !== $project->getCustomer()->getId()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Customer mismatch for project "%s" with attached customer "%s" and new customer "%s"',
                    $project->getName(),
                    $project->getCustomer()->getName(),
                    $customer->getName()
                )
            );
        }

        return $project;
    }

    private function findCustomer(string $customerName): Customer
    {
        $customerName = trim($customerName);
        $key = strtolower($customerName);

        if (!\array_key_exists($key, $this->customerCache)) {
            $customer = $this->findCustomerByName($customerName);

            if ($customer === null) {
                $customer = $this->customerService->createNewCustomer();
                $customer->setName($customerName);
            }

            $this->customerCache[$key] = $customer;
        }

        return $this->customerCache[$key];
    }

    /**
     * Find the unique project name inside $entry.
     *
     * @param array $entry
     * @return string
     * @throws UnsupportedFormatException
     */
    protected function findProjectName(array $entry): string
    {
        foreach ($entry as $name => $value) {
            switch (strtolower($name)) {
                case 'project':
                case 'projectname':
                case 'project name':
                case 'project-name':
                case 'name':
                    if (!empty($value)) {
                        return substr($value, 0, 149);
                    }
            }
        }

        throw new UnsupportedFormatException('Missing project name, expected in one of the columns: "Name", "Project , "Project Name", "Project-Name", "ProjectName"');
    }

    /**
     * Find the unique project name inside $entry.
     *
     * @param array $entry
     * @return string
     * @throws UnsupportedFormatException
     */
    protected function findCustomerName(array $entry): string
    {
        foreach ($entry as $name => $value) {
            switch (strtolower($name)) {
                case 'customer':
                case 'customername':
                case 'customer-name':
                case 'customer name':
                    if (!empty($value)) {
                        return substr($value, 0, 149);
                    }
            }
        }

        throw new UnsupportedFormatException('Missing customer name, expected in one of the columns: "Customer", "Customer Name", "Customer-Name" or "CustomerName"');
    }

    /**
     * Applies all supported values from $entry to $project.
     *
     * @param Project $project
     * @param array $entry
     * @param array $options
     * @return void
     */
    abstract protected function convertEntry(Project $project, array $entry, array $options = []): void;
}
