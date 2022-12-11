<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use App\Form\Type\UserRoleType;
use App\Form\UserEditType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserApiEditForm extends UserEditType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        if ($options['include_roles']) {
            $builder->add('roles', UserRoleType::class, [
                'label' => 'roles',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_protection' => false,
            'include_roles' => true,
        ]);
    }
}
