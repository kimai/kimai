<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\ActivityRate;
use App\Entity\Rate;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityRateForm extends AbstractRateForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currency = null;

        if (!empty($options['data'])) {
            /** @var ActivityRate $rate */
            $rate = $options['data'];

            if (null !== $rate->getActivity() && !$rate->getActivity()->isGlobal()) {
                $currency = $rate->getActivity()->getProject()->getCustomer()->getCurrency();
            }
        }

        $this->addFields($builder, $currency);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityRate::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'expand_users' => true,
            'csrf_token_id' => 'admin_customer_rate_edit',
            'attr' => [
                'data-form-event' => 'kimai.activityUpdate'
            ],
        ]);
    }
}
