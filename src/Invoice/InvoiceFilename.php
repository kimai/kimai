<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Utils\FileHelper;

final class InvoiceFilename
{
    private string $filename;

    public function __construct(InvoiceModel $model)
    {
        $filename = $model->getInvoiceNumber();

        $filename = str_replace(['/', '\\'], '-', $filename);

        $company = $model->getCustomer()->getCompany();
        if (empty($company)) {
            $company = $model->getCustomer()->getName();
        }

        if (!empty($company)) {
            $filename .= '-' . $this->convert($company);
        }

        if (null !== $model->getQuery()) {
            $projects = $model->getQuery()->getProjects();
            if (\count($projects) === 1) {
                $filename .= '-' . $this->convert($projects[0]->getName());
            }
        }

        $this->filename = $filename;
    }

    private function convert(string $filename): string
    {
        return FileHelper::convertToAsciiFilename($filename);
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function __toString(): string
    {
        return $this->getFilename();
    }
}
