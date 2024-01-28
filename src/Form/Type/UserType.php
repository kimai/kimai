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
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

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
            // includes the current user if it is a system-account, which is especially useful for forms pages,
            // which have a user switcher and display the logged-in user by default
            'include_current_user_if_system_account' => false,
            'documentation' => [
                'type' => 'integer',
                'description' => 'User ID',
            ],
        ]);

        $resolver->setDefault('choices', function (Options $options) {
            $query = new UserFormTypeQuery();
            $query->setUser($options['user']);

            if ($options['include_disabled'] === true) {
                $query->setVisibility(VisibilityInterface::SHOW_BOTH);
            }

            $qb = $this->userRepository->getQueryBuilderForFormType($query);
            $users = $qb->getQuery()->getResult();

            $ignoreIds = [];
            /** @var User $user */
            foreach ($options['ignore_users'] as $user) {
                $ignoreIds[] = $user->getId();
            }

            $users = array_filter($users, function (User $user) use ($ignoreIds) {
                if ($user->getId() === null) {
                    return false;
                }

                return !\in_array($user->getId(), $ignoreIds, true);
            });

            /** @var array<int, User> $userById */
            $userById = [];
            /** @var User $user */
            foreach ($users as $user) {
                $userById[$user->getId()] = $user;
            }

            $includeUsers = $options['include_users'];
            if ($options['include_current_user_if_system_account'] === true) {
                if ($options['user'] instanceof User && $options['user']->isSystemAccount()) {
                    $includeUsers[] = $options['user'];
                }
            }

            /** @var User $user */
            foreach ($includeUsers as $user) {
                if ($user->getId() !== null && !\array_key_exists($user->getId(), $userById)) {
                    $userById[$user->getId()] = $user;
                }
            }

            usort($userById, function (User $a, User $b) {
                return $a->getDisplayName() <=> $b->getDisplayName();
            });

            return array_values($userById);
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
