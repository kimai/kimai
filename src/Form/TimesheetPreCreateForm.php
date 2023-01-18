<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\DescriptionType;
use App\Form\Type\MetaFieldsCollectionType;
use App\Form\Type\TagsInputType;
use App\Form\Type\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Values that are allowed to be pre-set via URL.
 */
final class TimesheetPreCreateForm extends AbstractType
{
    use FormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addProject($builder, true, null, null, ['required' => false]);
        $this->addActivity($builder, null, null, ['required' => false]);
        $builder->add('description', DescriptionType::class, ['required' => false]);
        $builder->add('tags', TagsInputType::class, ['required' => false]);
        $builder->add('metaFields', MetaFieldsCollectionType::class);
        if ($options['include_user']) {
            $builder->add('user', UserType::class, ['required' => false]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'include_user' => false,
            'method' => 'GET',
            'validation_groups' => ['none'] // otherwise the default timesheet validations would trigger
        ]);
    }
}
