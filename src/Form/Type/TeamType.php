<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Team;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
            'query_builder' => function (TeamRepository $repo) {
                return $repo->createQueryBuilder('t')->orderBy('t.name', 'ASC');
            },
            'choice_label' => function (Team $team) {
                return $team->getName();
            },
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return EntityType::class;
    }
}
