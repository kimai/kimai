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

/**
 * Custom form field type to select an activity.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ActivityType extends AbstractType
{

    /**
     * @param Activity $activity
     * @param $key
     * @param $index
     * @return string
     */
    public function groupBy(Activity $activity, $key, $index)
    {
        return $activity->getProject()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'TimesheetBundle:Activity',
            'choice_label' => 'name',
            'choice_value' => 'id',
            'group_by' => array($this, 'groupBy'),
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
