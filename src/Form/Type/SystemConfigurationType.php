<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\Model\Configuration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to edit a system configuration.
 */
class SystemConfigurationType extends AbstractType
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
                /** @var Configuration $preference */
                $preference = $event->getData();

                if (!($preference instanceof Configuration)) {
                    return;
                }

                // prevents unconfigured values from showing up in the form
                if ($preference->getType() === null) {
                    return;
                }

                $required = true;
                if (CheckboxType::class == $preference->getType()) {
                    $required = false;
                }

                $type = $preference->getType();
                if (!$preference->isEnabled()) {
                    $type = HiddenType::class;
                }

                $event->getForm()->add('value', $type, [
                    'label' => 'label.' . ($preference->getLabel() ?? $preference->getName()),
                    'constraints' => $preference->getConstraints(),
                    'required' => $required,
                    'disabled' => !$preference->isEnabled(),
                    'translation_domain' => $preference->getTranslationDomain(),
                ]);
            }
        );
        $builder->add('name', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
        ]);
    }
}
