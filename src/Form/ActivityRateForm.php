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
use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityRateForm extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currency = null;

        if ($options['data']) {
            /** @var ActivityRate $rate */
            $rate = $options['data'];

            if (null !== $rate->getActivity() && !$rate->getActivity()->isGlobal()) {
                $currency = $rate->getActivity()->getProject()->getCustomer()->getCurrency();
            }
        }

        $builder
            ->add('user', UserType::class, [
                'required' => false,
            ])
            ->add('rate', MoneyType::class, [
                'label' => 'label.rate',
                'attr' => [
                    'autofocus' => 'autofocus'
                ],
                'currency' => $currency,
            ])
            ->add('isFixed', YesNoType::class, [
                'label' => 'label.fixedRate'
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Rate::class,
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
