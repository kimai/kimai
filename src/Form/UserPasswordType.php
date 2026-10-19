<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\User;
use App\Validator\Constraints\NotCurrentPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * Defines the form used to set the user password.
 * @extends AbstractType<User>
 */
final class UserPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['require_current_password'] === true) {
            $builder->add('currentPassword', PasswordType::class, [
                'label' => 'password_current',
                'mapped' => false,
                'attr' => ['autocomplete' => 'current-password'],
                'block_prefix' => 'secret',
                // also reports an empty value as violation
                'constraints' => [new UserPassword(groups: ['PasswordUpdate'])],
            ]);
        }

        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'password',
                    'attr' => ['autocomplete' => 'new-password'],
                    'block_prefix' => 'secret'
                ],
                'second_options' => [
                    'label' => 'password_repeat',
                    'attr' => ['autocomplete' => 'new-password'],
                    'block_prefix' => 'secret'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['PasswordUpdate'],
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'edit_user_password',
            // only for users changing their own password, as UserPassword validates against the logged-in user
            'require_current_password' => false,
            // only for users changing their own password, otherwise the error message reveals the current password
            'deny_current_password' => false,
        ]);

        $resolver->setAllowedTypes('require_current_password', 'bool');
        $resolver->setAllowedTypes('deny_current_password', 'bool');

        $resolver->setNormalizer('constraints', function (Options $options, mixed $constraints): array {
            if (!\is_array($constraints)) {
                $constraints = $constraints === null ? [] : [$constraints];
            }

            if ($options['deny_current_password'] === true) {
                $constraints[] = new NotCurrentPassword(groups: ['PasswordUpdate']);
            }

            return $constraints;
        });
    }
}
