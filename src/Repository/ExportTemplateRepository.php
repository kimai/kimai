<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\ExportTemplate;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<ExportTemplate>
 */
class ExportTemplateRepository extends EntityRepository
{
    public function saveExportTemplate(ExportTemplate $template): void
    {
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    public function removeExportTemplate(ExportTemplate $template): void
    {
        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
    }
}
