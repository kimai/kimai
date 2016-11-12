<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Form;

use AppBundle\Entity\User;
use AppBundle\Form\Type\LanguageType;
use AppBundle\Form\Type\YesNoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to create and manipulate Users.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserEditType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // string - length 160
            ->add('alias', null, [
                'label' => 'label.alias',
            ])
            // string - length 50
            ->add('title', null, [
                //'attr' => ['autofocus' => true],
                'label' => 'label.title',
            ])
            // string - length 160
            ->add('email', null, [
                'label' => 'label.email',
            ])
            // string - length 5
            ->add('language', LanguageType::class, [
                'label' => 'label.language',
            ])
            // boolean
            ->add('active', YesNoType::class, [
                'label' => 'label.active',
            ])
            // TODO avatar
            // TODO roles - see ProfileController::getRoles()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'edit_user_profile',
        ]);
    }
}
