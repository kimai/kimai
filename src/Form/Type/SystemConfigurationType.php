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
final class SystemConfigurationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                /** @var Configuration $preference */
                $preference = $event->getData();

                if (!($preference instanceof Configuration)) {
                    return;
                }

                // prevents unconfigured values from showing up in the form
                if ($preference->getType() === null) {
                    return;
                }

                $required = $preference->isRequired();
                if (CheckboxType::class === $preference->getType() || YesNoType::class === $preference->getType()) {
                    $required = false;
                }

                $type = $preference->getType();
                if (!$preference->isEnabled()) {
                    $type = HiddenType::class;
                }

                $options = [
                    'label' => $preference->getLabel() ?? $preference->getName(),
                    'constraints' => $preference->getConstraints(),
                    'required' => $required,
                    'disabled' => !$preference->isEnabled(),
                    'translation_domain' => $preference->getTranslationDomain(),
                ];

                $event->getForm()->add('value', $type, array_merge($options, $preference->getOptions()));
            }
        );
        $builder->add('name', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Configuration::class,
        ]);
    }
}
