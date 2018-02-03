<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\InvoiceTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * $ php bin/console doctrine:fixtures:load
 */
class InvoiceFixtures extends Fixture
{

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadInvoiceTemplates($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    private function loadInvoiceTemplates(ObjectManager $manager)
    {
        $template = new InvoiceTemplate();
        $template
            ->setName('Invoice')
            ->setTitle('Your company name')
            ->setCompany('Kimai, Inc.')
            ->setVat(19)
            ->setDueDays(14)
            ->setPaymentTerms(
'I would like to thank you for your confidence and will gladly be there for you in the future.
Please transfer the total amount within 14 days to the given account and use the invoice number as reference.'
            )
            ->setAddress(
'795 Folsom Ave, Suite 600
San Francisco, CA 94107
Phone: (804) 123-5432
Email: info@almasaeedstudio.com'
            )
        ;

        $manager->persist($template);
        $manager->flush();
    }
}
