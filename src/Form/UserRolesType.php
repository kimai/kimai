<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\User;
use App\Form\Type\UserRoleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to set roles for a User.
 * @extends AbstractType<User>
 */
final class UserRolesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('roles', UserRoleType::class, [
                'multiple' => true,
                'expanded' => true,
                'restrict_to_assignable' => true,
            ])
        ;

        $currentUser = $options['user'];
        if (!$currentUser instanceof User) {
            return;
        }

        // the roles a user is not allowed to assign are hidden from the form (see UserRoleType),
        // so a plain submit would strip them from the edited user.
        // remember those roles before binding and re-add them afterward, so they can never be
        // removed by someone who is not allowed to manage them in the first place.
        $preservedRoles = [];

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($currentUser, &$preservedRoles): void {
            $user = $event->getData();
            if (!$user instanceof User) {
                return;
            }

            foreach ($user->getRoles() as $role) {
                if (!$currentUser->canAssignRole($role)) {
                    $preservedRoles[] = $role;
                }
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use (&$preservedRoles): void {
            $user = $event->getData();
            if (!$user instanceof User) {
                return;
            }

            foreach ($preservedRoles as $role) {
                $user->addRole($role);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['RolesUpdate'],
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'edit_user_roles',
        ]);
    }
}
