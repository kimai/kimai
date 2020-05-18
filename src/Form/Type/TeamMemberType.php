<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Select a user that
 */
class TeamMemberType extends AbstractType
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
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (UserRepository $repo) use ($options) {
                $qb = $repo->createQueryBuilder('u');
                $qb
                    ->andWhere($qb->expr()->eq('u.enabled', ':enabled'))
                    ->setParameter('enabled', true, \PDO::PARAM_BOOL)
                    ->orderBy('u.username', 'ASC');

                /** @var User $user */
                $user = $options['user'];

                if (null !== $user && !$user->getTeams()->isEmpty() && !$user->isSuperAdmin() && !$user->isAdmin()) {
                    $qb
                        ->leftJoin('u.teams', 'teams')
                        ->leftJoin('teams.users', 'users')
                        ->andWhere($qb->expr()->isMemberOf(':teams', 'u.teams'))
                        ->setParameter('teams', $user->getTeams());
                }

                return $qb;
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
