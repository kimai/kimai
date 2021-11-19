<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export;

use App\Entity\Customer;
use App\Repository\Query\TimesheetQuery;
use App\Utils\FileHelper;

final class ExportFilename
{
    /**
     * @var string
     */
    private $filename;

    public function __construct(TimesheetQuery $query)
    {
        $filename = date('Ymd');
        $hasName = false;

        $customers = $query->getCustomers();
        if (\count($customers) === 1) {
            $filename .= '-' . $this->convert($this->getCustomerName($customers[0]));
            $hasName = true;
        }

        $projects = $query->getProjects();
        if (\count($projects) === 1) {
            if (!$hasName) {
                $filename .= '-' . $this->convert($this->getCustomerName($projects[0]->getCustomer()));
            }
            $filename .= '-' . $this->convert($projects[0]->getName());
            $hasName = true;
        }

        $users = $query->getUsers();
        if (\count($users) === 1) {
            $filename .= '-' . $this->convert($users[0]->getDisplayName());
            $hasName = true;
        }

        if (!$hasName) {
            $filename .= '-kimai-export';
        }

        $filename = str_replace(['/', '\\'], '-', $filename);

        $this->filename = $filename;
    }

    private function getCustomerName(Customer $customer): string
    {
        $company = $customer->getCompany();
        if (empty($company)) {
            $company = $customer->getName();
        }

        return $company;
    }

    private function convert(string $filename): string
    {
        return FileHelper::convertToAsciiFilename($filename);
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function __toString()
    {
        return $this->getFilename();
    }
}
