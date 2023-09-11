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
use App\Utils\Color;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a user.
 * @extends AbstractType<User>
 */
final class UserType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => User::class,
            'label' => 'user',
            'choice_label' => function (User $user) {
                return $user->getDisplayName();
            },
            'choice_attr' => function (User $user) {
                $color = $user->getColor();

                if ($color === null) {
                    $color = (new Color())->getRandom($user->getDisplayName());
                }

                return [
                    'data-id' => $user->getId(),
                    'data-color' => $color,
                    'data-title' => $user->getTitle(),
                    'data-username' => $user->getUserIdentifier(),
                    'data-alias' => $user->getAlias(),
                    'data-initials' => $user->getInitials(),
                    'data-accountNumber' => $user->getAccountNumber(),
                    'data-display' => $user->getDisplayName(),
                ];
            },
            'choice_translation_domain' => false,
            // whether disabled users should be included in the result list
            'include_disabled' => false,
            // an array of users that should not be included in the result list
            'ignore_users' => [],
            // an array of users, which will always be included in the result list
            // why? if the base entity could include disabled users, which should not be hidden in/removed from the list
            // e.g. when editing a team that has disabled users, these users would be removed silently
            // see https://github.com/kimai/kimai/pull/1841
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

                foreach ($options['ignore_users'] as $userToIgnore) {
                    $query->addUserToIgnore($userToIgnore);
                }

                if (!empty($options['include_users'])) {
                    $query->setUsersAlwaysIncluded($options['include_users']);
                }

                return $repo->getQueryBuilderForFormType($query);
            };
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-select-attributes' => 'color,title,username,initials,accountNumber,alias',
            'data-renderer' => 'color',
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
