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
use App\Repository\Query\ActivityFormTypeQuery;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
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
     * @param string $key
     * @param mixed $value
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
            // documentation is for NelmioApiDocBundle
            'documentation' => [
                'type' => 'integer',
                'description' => 'Activity ID',
            ],
            'label' => 'label.activity',
            'class' => Activity::class,
            'choice_label' => [$this, 'choiceLabel'],
            'group_by' => [$this, 'groupBy'],
            'choice_attr' => [$this, 'choiceAttr'],
            'query_builder_for_user' => true,
            // @var Project|Project[]|int|int[]|null
            'projects' => null,
            // @var Activity|Activity[]|int|int[]|null
            'activities' => null,
            // @var Activity|null
            'ignore_activity' => null,
        ]);

        $resolver->setDefault('query_builder', function (Options $options) {
            return function (ActivityRepository $repo) use ($options) {
                $query = new ActivityFormTypeQuery($options['activities'], $options['projects']);

                if (true === $options['query_builder_for_user']) {
                    $query->setUser($options['user']);
                }

                if (null !== $options['ignore_activity']) {
                    $query->setActivityToIgnore($options['ignore_activity']);
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
