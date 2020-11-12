<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\User;
use App\Repository\Query\UserFormTypeQuery;
use App\Repository\Query\VisibilityInterface;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a user.
 */
class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => User::class,
            'label' => 'label.user',
            'choice_label' => function (User $user) {
                return $user->getDisplayName();
            },
            'choice_translation_domain' => false,
            // whether disabled users should be included in the result list
            'include_disabled' => false,
            // an array of users, which will always be included in the result list
            // why? if the base entity could include disabled users, which should not be hidden in/removed from the list
            // eg. when editing a team that has disabled users, these users would be removed silently
            // see https://github.com/kevinpapst/kimai2/pull/1841
            'include_users' => [],
            'documentation' => [
                'type' => 'integer',
                'description' => 'User ID',
            ],
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (UserRepository $repo) use ($options) {
                $query = new UserFormTypeQuery();
                $query->setUser($options['user']);

                if ($options['include_disabled'] === true) {
                    $query->setVisibility(VisibilityInterface::SHOW_BOTH);
                }

                if (!empty($options['include_users'])) {
                    $query->setUsersAlwaysIncluded($options['include_users']);
                }

                return $repo->getQueryBuilderForFormType($query);
            };
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
