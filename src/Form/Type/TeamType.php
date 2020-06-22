<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Team;
use App\Entity\User;
use App\Repository\Query\TeamQuery;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Team::class,
            'label' => 'label.team',
            'teamlead_only' => true,
            'choice_label' => function (Team $team) {
                return $team->getName();
            },
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (TeamRepository $repo) use ($options) {
                /** @var User $user */
                $user = $options['user'];
                $query = new TeamQuery();
                $query->setCurrentUser($user);

                if (!$options['teamlead_only']) {
                    $query->setTeams($user->getTeams()->toArray());
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
