<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\MetaTableTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to edit entity meta field.
 */
class EntityMetaDefinitionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var MetaTableTypeInterface $definition */
                $definition = $event->getData();

                if (!($definition instanceof MetaTableTypeInterface)) {
                    return;
                }

                // prevents unconfigured values from showing up in the form
                if (null === $definition->getType()) {
                    return;
                }

                $event->getForm()->add('value', $definition->getType(), array_merge([
                    'label' => $definition->getLabel(),
                    'constraints' => $definition->getConstraints(),
                    'required' => $definition->isRequired(),
                ], $definition->getOptions()));
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MetaTableTypeInterface::class,
        ]);
    }
}
