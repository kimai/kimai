<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\User;
use App\Security\RoleService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a user role.
 * @extends AbstractType<User>
 */
final class UserRoleType extends AbstractType
{
    public function __construct(private RoleService $roles)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'roles',
            'include_default' => false,
            // when true, the choices are limited to the roles the current user is allowed to
            // assign - only meaningful when this field is used to assign roles (create/edit),
            // not when it is used to filter the user list (e.g. in the toolbar)
            'restrict_to_assignable' => false,
        ]);

        $resolver->setAllowedTypes('restrict_to_assignable', 'bool');

        $resolver->setDefault('choices', function (Options $options): array {
            $roles = [];
            foreach ($this->roles->getAvailableNames() as $name) {
                $roles[$name] = $name;
            }

            if ($options['include_default'] !== true && isset($roles[User::DEFAULT_ROLE])) {
                unset($roles[User::DEFAULT_ROLE]);
            }

            $user = $options['user'];
            if ($options['restrict_to_assignable'] === true && $user instanceof User) {
                // only offer the roles the current user is allowed to assign
                foreach (array_keys($roles) as $roleName) {
                    if (!$user->canAssignRole($roleName)) {
                        unset($roles[$roleName]);
                    }
                }
            }

            return $roles;
        });
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
