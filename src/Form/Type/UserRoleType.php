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
 */
class UserRoleType extends AbstractType
{
    /**
     * @var RoleService
     */
    private $roles;

    public function __construct(RoleService $roles)
    {
        $this->roles = $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.roles',
            'include_default' => false,
        ]);

        $resolver->setDefault('choices', function (Options $options) {
            $roles = [];
            foreach ($this->roles->getAvailableNames() as $name) {
                $roles[$name] = $name;
            }

            if ($options['include_default'] !== true && isset($roles[User::DEFAULT_ROLE])) {
                unset($roles[User::DEFAULT_ROLE]);
            }

            return $roles;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
