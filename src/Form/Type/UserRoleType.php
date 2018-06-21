<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a user role.
 */
class UserRoleType extends AbstractType
{
    /**
     * @var string[]
     */
    protected $roles = [];

    /**
     * UserRolesType constructor.
     * @param string[] $roles
     */
    public function __construct(array $roles = [])
    {
        $this->roles = $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $roles = [];

        foreach ($this->roles as $key => $value) {
            $roles[$key] = $key;
            foreach ($value as $value2) {
                $roles[$value2] = $value2;
            }
        }

        $resolver->setDefaults([
            'label' => 'label.roles',
            'choices' => $roles,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
