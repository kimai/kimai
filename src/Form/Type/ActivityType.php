<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Activity;
use App\Form\Helper\ActivityHelper;
use App\Form\Helper\ProjectHelper;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select an activity.
 */
class ActivityType extends AbstractType
{
    private $activityHelper;
    private $projectHelper;

    public function __construct(ActivityHelper $activityHelper, ProjectHelper $projectHelper)
    {
        $this->activityHelper = $activityHelper;
        $this->projectHelper = $projectHelper;
    }

    public function getChoiceLabel(Activity $activity): string
    {
        return $this->activityHelper->getChoiceLabel($activity);
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(Activity $activity, $key, $index)
    {
        if (null === $activity->getProject()) {
            return null;
        }

        return $this->projectHelper->getChoiceLabel($activity->getProject());
    }

    /**
     * @param Activity $activity
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function getChoiceAttributes(Activity $activity, $key, $value)
    {
        if (null !== ($project = $activity->getProject())) {
            return ['data-project' => $project->getId(), 'data-currency' => $project->getCustomer()->getCurrency()];
        }

        return [];
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
            'choice_label' => [$this, 'getChoiceLabel'],
            'choice_attr' => [$this, 'getChoiceAttributes'],
            'group_by' => [$this, 'groupBy'],
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

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-option-pattern' => $this->activityHelper->getChoicePattern(),
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
