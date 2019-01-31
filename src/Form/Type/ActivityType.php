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

        return $activity->getProject()->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function choiceLabel(Activity $activity)
    {
        return $activity->getName();
    }

    /**
     * @param Activity $choiceValue
     * @param $key
     * @param $value
     * @return array
     */
    public function choiceAttr($choiceValue, $key, $value)
    {
        $project = null;

        if (!($choiceValue instanceof Activity)) {
            return [];
        }

        if (null !== $choiceValue->getProject()) {
            $project = $choiceValue->getProject()->getId();
        }

        return ['data-project' => $project];
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
            'choice_attr' => [$this, 'choiceAttr'],
            'query_builder' => function (ActivityRepository $repo) {
                return $repo->builderForEntityType();
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
