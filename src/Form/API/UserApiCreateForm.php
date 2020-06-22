<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use App\Form\Type\UserRoleType;
use App\Form\UserCreateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserApiCreateForm extends UserCreateType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('plainPassword');

        $builder->add('plainPassword', PasswordType::class, [
            'required' => true,
            'label' => 'label.password',
            'documentation' => [
                'type' => 'string',
                'description' => 'Plain text password',
            ],
        ]);

        if ($options['include_roles']) {
            $builder->add('roles', UserRoleType::class, [
                'label' => 'label.roles',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_protection' => false,
            'include_roles' => true,
            'include_add_more' => false,
        ]);
    }
}
