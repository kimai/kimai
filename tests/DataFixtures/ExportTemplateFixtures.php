<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\ExportTemplate;
use Doctrine\Persistence\ObjectManager;

final class ExportTemplateFixtures implements TestFixture
{
    /**
     * @return ExportTemplate[]
     */
    public function load(ObjectManager $manager): array
    {
        $template1 = new ExportTemplate();
        $template1->setRenderer('csv');
        $template1->setLanguage('de');
        $template1->setTitle('CSV Test');
        $template1->setColumns(['date', 'user.name', 'duration', 'customer.name']);
        $manager->persist($template1);

        $template2 = new ExportTemplate();
        $template2->setRenderer('xlsx');
        $template2->setLanguage('en');
        $template2->setTitle('Excel Test');
        $template2->setColumns(['date', 'user.name', 'duration_seconds', 'project.name']);
        $manager->persist($template2);

        $manager->flush();

        return [$template1, $template2];
    }
}
