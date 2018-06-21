<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to create Users.
 */
class UserCreateType extends UserEditType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, [
                'label' => 'label.username',
                'required' => true
            ])
            ->add('plainPassword', RepeatedType::class, [
                'required' => true,
                'type' => PasswordType::class,
                'first_options' => ['label' => 'label.password'],
                'second_options' => ['label' => 'label.password_repeat'],
            ]);

        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function __configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'Kimai:User',
        ]);
    }
}
