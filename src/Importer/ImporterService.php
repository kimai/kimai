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

class ImporterService
{
    private $customers;
    private $projects;
    /**
     * @var ProjectImporterInterface[]
     */
    private $projectImporter = [];
    /**
     * @var CustomerImporterInterface[]
     */
    private $customerImporter = [];

    public function __construct(CustomerService $customers, ProjectService $projects)
    {
        $this->customers = $customers;
        $this->projects = $projects;
    }

    public function registerProjectImporter(string $name, ProjectImporterInterface $importer): void
    {
        $this->projectImporter[$name] = $importer;
    }

    public function registerCustomerImporter(string $name, CustomerImporterInterface $importer): void
    {
        $this->customerImporter[$name] = $importer;
    }

    public function getProjectImporter(string $name): ProjectImporterInterface
    {
        if (!\array_key_exists('default', $this->projectImporter)) {
            $this->registerProjectImporter('default', new DefaultProjectImporter($this->projects, $this->customers));
        }

        if (!\array_key_exists($name, $this->projectImporter)) {
            throw new \InvalidArgumentException('Unknown project importer: ' . $name);
        }

        return $this->projectImporter[$name];
    }

    public function getCustomerImporter(string $name): CustomerImporterInterface
    {
        if (!\array_key_exists('default', $this->customerImporter)) {
            $this->registerCustomerImporter('default', new DefaultCustomerImporter($this->customers));
            $this->registerCustomerImporter('grandtotal', new GrandtotalCustomerImporter($this->customers));
        }

        if (!\array_key_exists($name, $this->customerImporter)) {
            throw new \InvalidArgumentException('Unknown customer importer: ' . $name);
        }

        return $this->customerImporter[$name];
    }

    public function importProject(Project $project): void
    {
        if ($project->getId() === null) {
            $this->projects->saveNewProject($project);
        } else {
            $this->projects->updateProject($project);
        }
    }

    public function getReader(string $name): ImportReaderInterface
    {
        switch ($name) {
            case 'csv':
                return new CsvReader(',');
            case 'csv-semicolon':
                return new CsvReader(';');
        }

        throw new \InvalidArgumentException('Unknown import reader: ' . $name);
    }

    public function importCustomer(Customer $customer): void
    {
        if ($customer->getId() === null) {
            $this->customers->saveNewCustomer($customer);
        } else {
            $this->customers->updateCustomer($customer);
        }
    }
}
