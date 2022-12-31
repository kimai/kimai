<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceStatusType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'status',
            'multiple' => true,
            'choices' => [
                'status.' . Invoice::STATUS_NEW => Invoice::STATUS_NEW,
                'status.' . Invoice::STATUS_PENDING => Invoice::STATUS_PENDING,
                'status.' . Invoice::STATUS_PAID => Invoice::STATUS_PAID,
                'status.' . Invoice::STATUS_CANCELED => Invoice::STATUS_CANCELED,
            ],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
