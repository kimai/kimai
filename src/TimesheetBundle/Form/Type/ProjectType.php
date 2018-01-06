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
use TimesheetBundle\Entity\Project;

/**
 * Custom form field type to select a project.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'TimesheetBundle:Project',
            'choice_label' => 'name',
            'choice_value' => 'id',
            'group_by' => function(Project $project, $key, $index) {
                return $project->getCustomer()->getName();
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
