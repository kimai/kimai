<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

use App\Entity\Project;
use App\Utils\FileHelper;

final class InvoiceFilename
{
    /**
     * @var string
     */
    private $filename;

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
                $pName = $projects[0];
                if ($pName instanceof Project) {
                    $filename .= '-' . $this->convert($pName->getName());
                }
            }
        }

        $this->filename = $filename;
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
