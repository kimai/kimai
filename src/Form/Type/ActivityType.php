<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select an activity.
 */
class ActivityType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function groupBy(Activity $activity, $key, $index)
    {
        if (null === $activity->getProject()) {
            return null;
        }
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
            'class' => Activity::class,
            'choice_label' => [$this, 'choiceLabel'],
            'group_by' => [$this, 'groupBy'],
            'query_builder' => function (ActivityRepository $repo) {
                return $repo->builderForEntityType();
            },
            'choice_attr' => function(Activity $activity, $key, $value) {
                $attributes = [];
                if (null !== $activity->getProject()) {
                    $attributes['data-project'] = $activity->getProject()->getId();
                }
                return $attributes;
            },
            //'attr' => ['class' => 'selectpicker', 'data-size' => 10, 'data-live-search' => true, 'data-width' => '100%']
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
