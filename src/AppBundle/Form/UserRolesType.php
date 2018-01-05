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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used to set roles for a User.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class UserRolesType extends AbstractType
{
    /**
     * @var string[]
     */
    protected $roles = [];

    /**
     * UserRolesType constructor.
     * @param string[] $roles
     */
    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->roles as $key => $value) {
            $roles[$key] = $key;
            foreach ($value as $value2) {
                $roles[$value2] = $value2;
            }
        }

        $builder
            // string[]
            ->add('roles', ChoiceType::class, [
                'label' => 'label.roles',
                'multiple' => true,
                'choices' => $roles,
            ])
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
            'csrf_token_id' => 'edit_user_roles',
        ]);
    }
}
