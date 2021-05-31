<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Role;
use FOS\RestBundle\Validator\Constraints\Regex;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form used to edit roles.
 */
class RoleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
                'help' => 'Allowed character: A-Z and _',
                'constraints' => [
                    new Regex(['pattern' => '/^[a-zA-Z_]{5,}$/'])
                ],
                'attr' => [
                    'maxlength' => 50
                ]
            ])
        ;

        // help the user to figure out the allowed name
        $builder->get('name')->addViewTransformer(
            new CallbackTransformer(
                function ($roleName) {
                    if (\is_string($roleName)) {
                        $roleName = str_replace(' ', '_', $roleName);
                        $roleName = str_replace('-', '_', $roleName);
                        $roleName = strtoupper($roleName);
                    }

                    return $roleName;
                },
                function ($roleName) {
                    return $roleName;
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'edit_role',
            'attr' => [
                'data-form-event' => 'kimai.userRoleUpdate',
                'data-msg-success' => 'action.update.success',
                'data-msg-error' => 'action.update.error',
            ],
        ]);
    }
}
