<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\User;
use App\Form\Type\DurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

/**
 * @extends AbstractType<User>
 */
final class UserContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dayOptions = [
            'translation_domain' => 'system-configuration',
            'constraints' => [
                new GreaterThanOrEqual(0)
            ],
        ];

        $builder
            ->add('workHoursMonday', DurationType::class, array_merge(['label' => 'Monday'], $dayOptions))
            ->add('workHoursTuesday', DurationType::class, array_merge(['label' => 'Tuesday'], $dayOptions))
            ->add('workHoursWednesday', DurationType::class, array_merge(['label' => 'Wednesday'], $dayOptions))
            ->add('workHoursThursday', DurationType::class, array_merge(['label' => 'Thursday'], $dayOptions))
            ->add('workHoursFriday', DurationType::class, array_merge(['label' => 'Friday'], $dayOptions))
            ->add('workHoursSaturday', DurationType::class, array_merge(['label' => 'Saturday'], $dayOptions))
            ->add('workHoursSunday', DurationType::class, array_merge(['label' => 'Sunday'], $dayOptions))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'edit_user_contract',
        ]);
    }
}
