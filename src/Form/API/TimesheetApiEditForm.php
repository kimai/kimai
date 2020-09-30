<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use App\Form\TimesheetEditForm;
use App\Form\Type\TagsInputType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimesheetApiEditForm extends TimesheetEditForm
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('metaFields');

        if ($builder->has('user')) {
            $builder->get('user')->setRequired(false);
        }
    }

    /**
     * Method added to prevent API BC breaks.
     *
     * @param FormBuilderInterface $builder
     */
    protected function addTags(FormBuilderInterface $builder)
    {
        $builder
            ->add('tags', TagsInputType::class, [
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_duration' => false,
            // overwritten and changed to default "true",
            // because the docs are cached without these fields otherwise
            'include_user' => true,
            'include_exported' => true,
            'include_rate' => true,
        ]);
    }
}
