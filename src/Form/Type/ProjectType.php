<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a project.
 */
class ProjectType extends AbstractType
{
    /**
     * @param Project $choiceValue
     * @param $key
     * @param $value
     * @return array
     */
    public function choiceAttr($choiceValue, $key, $value)
    {
        $customer = null;

        if (!($choiceValue instanceof Project)) {
            return [];
        }

        if (null !== $choiceValue->getCustomer()) {
            $customer = $choiceValue->getCustomer()->getId();
        }

        return ['data-customer' => $customer];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.project',
            'class' => Project::class,
            'choice_label' => 'name',
            'choice_attr' => [$this, 'choiceAttr'],
            'group_by' => function (Project $project, $key, $index) {
                return $project->getCustomer()->getName();
            },
            'query_builder' => function (ProjectRepository $repo) {
                return $repo->builderForEntityType(null);
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
