<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Customer;
use App\Entity\Timesheet;
use App\Form\Type\ActivityGroupedWithCustomerNameType;
use App\Repository\ActivityRepository;

/**
 * Defines the form used to manipulate Timesheet entries.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class TimesheetEditForm extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Timesheet $entry */
        $entry = $options['data'];

        $activity = null;
        if ($entry->getId() !== null) {
            $activity = $entry->getActivity();
        }

        $builder
            // datetime
            ->add('begin', DateTimeType::class, [
                'label' => 'label.begin',
                'date_widget' => 'single_text',
            ])
            // datetime
            ->add('end', DateTimeType::class, [
                'label' => 'label.end',
                'date_widget' => 'single_text',
                'required' => false,
            ])
            // Activity
            ->add('activity', ActivityGroupedWithCustomerNameType::class, [
                'label' => 'label.activity',
                'query_builder' => function (ActivityRepository $repo) use ($activity) {
                    return $repo->builderForEntityType($activity);
                },
            ])
            // customer
            ->add('description', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
            ])
            // string
            ->add('rate', MoneyType::class, [
                'label' => 'label.rate',
                'currency' => $builder->getOption('currency'),
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Timesheet::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'timesheet_edit',
            'currency' => Customer::DEFAULT_CURRENCY,
        ]);
    }
}
