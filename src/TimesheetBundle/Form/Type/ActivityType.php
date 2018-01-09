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
    public function groupBy(Activity $activity, $key, $index)
    {
        return '[' . $activity->getProject()->getId() . '] ' . $activity->getProject()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function choiceLabel(Activity $activity)
    {
        return $activity->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.activity',
            'class' => 'TimesheetBundle:Activity',
            'choice_label' => [$this, 'choiceLabel'],
            'group_by' => [$this, 'groupBy'],
            'query_builder' => function (ActivityRepository $repo) {
                return $repo->builderForEntityType(null);
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
