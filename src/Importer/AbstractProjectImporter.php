<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Importer;

use App\Configuration\FormConfiguration;
use App\Entity\Customer;
use App\Entity\Project;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;

abstract class AbstractProjectImporter
{
    private $projectRepository;
    private $customerRepository;
    private $configuration;
    /**
     * @var Customer[]
     */
    private $customerCache = [];

    public function __construct(ProjectRepository $projectRepository, CustomerRepository $customerRepository, FormConfiguration $configuration)
    {
        $this->projectRepository = $projectRepository;
        $this->customerRepository = $customerRepository;
        $this->configuration = $configuration;
    }

    protected function findProjectByName(string $name): ?Project
    {
        return $this->projectRepository->findOneBy(['name' => $name]);
    }

    protected function findProjectByOrderNumber(string $number): ?Project
    {
        return $this->projectRepository->findOneBy(['order_number' => $number]);
    }

    protected function findCustomerByName(string $name): ?Customer
    {
        return $this->customerRepository->findOneBy(['name' => $name]);
    }

    public function convertEntryToProject(array $entry): Project
    {
        $project = $this->findProject($entry);

        $this->convertEntry($project, $entry);

        return $project;
    }

    protected function createNewProject(string $name): Project
    {
        $project = new Project();
        $project->setName(substr($name, 0, 149));

        return $project;
    }

    protected function findProject(array $entry): ?Project
    {
        $name = $this->findProjectName($entry);
        $project = $this->findProjectByName($name);

        if ($project === null) {
            $project = $this->createNewProject($name);
        }

        $name = $this->findCustomerName($entry);
        $customer = $this->findCustomer($name);
        $project->setCustomer($customer);

        return $project;
    }

    private function findCustomer(string $customerName): Customer
    {
        if (!\array_key_exists($customerName, $this->customerCache)) {
            $customer = $this->findCustomerByName($customerName);

            if ($customer === null) {
                $customer = new Customer();
                $customer->setName($customerName);
                $customer->setCountry($this->configuration->getCustomerDefaultCountry());
                $timezone = date_default_timezone_get();
                if (null !== $this->configuration->getCustomerDefaultTimezone()) {
                    $timezone = $this->configuration->getCustomerDefaultTimezone();
                }
                $customer->setTimezone($timezone);
            }

            $this->customerCache[$customerName] = $customer;
        }

        return $this->customerCache[$customerName];
    }

    protected function getDefaultTimezone(): string
    {
        $timezone = date_default_timezone_get();
        if (null !== $this->configuration->getCustomerDefaultTimezone()) {
            $timezone = $this->configuration->getCustomerDefaultTimezone();
        }

        return $timezone;
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
                        return $value;
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
                        return $value;
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
     * @return mixed
     */
    abstract protected function convertEntry(Project $project, array $entry);
}
