<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Entity\Activity;
use TimesheetBundle\Repository\ActivityRepository;
use TimesheetBundle\Repository\Query\ActivityQuery;

/**
 * Custom form field type to select an activity.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'TimesheetBundle:Activity',
            'choice_label' => 'name',
            'group_by' => function (Activity $activity, $key, $index) {
                return '[' . $activity->getProject()->getId() . '] ' . $activity->getProject()->getName();
            },
            'query_builder' => function (ActivityRepository $repo) {
                $query = new ActivityQuery();
                $query->setVisibility(ActivityQuery::SHOW_BOTH);
                $query->setResultType(ActivityQuery::RESULT_TYPE_QUERYBUILDER);
                return $repo->findByQuery($query);
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
