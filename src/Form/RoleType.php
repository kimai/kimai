<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Role;
use App\Validator\Constraints\RoleName;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'name',
            'help' => 'Allowed character: A-Z and _',
            'constraints' => [
                new NotBlank(),
                new RoleName(),
            ],
            'attr' => [
                'maxlength' => 50
            ]
        ]);

        $replacer = function ($roleName): ?string {
            if ($roleName === null) {
                return null;
            }

            if (\is_string($roleName)) {
                $roleName = preg_replace('/[^a-zA-Z_]/', '_', $roleName);
                $roleName = preg_replace('/_+/', '_', $roleName ?? '');
                $roleName = ltrim($roleName ?? '', '_');
                $roleName = rtrim($roleName, '_');
                $roleName = strtoupper($roleName);
            }

            return $roleName;
        };

        // help the user to figure out the allowed name
        $builder->get('name')->addViewTransformer(
            new CallbackTransformer(
                function ($roleName) use ($replacer) {
                    return $replacer($roleName);
                },
                function ($roleName) {
                    return $roleName;
                }
            )
        );
        $builder->get('name')->addModelTransformer(
            new CallbackTransformer(
                function ($roleName) {
                    return $roleName;
                },
                function ($roleName) use ($replacer) {
                    return $replacer($roleName);
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
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
