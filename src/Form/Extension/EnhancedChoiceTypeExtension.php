<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Converts normal select boxes into javascript enhanced versions.
 */
final class EnhancedChoiceTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class, ChoiceType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($options['selectpicker']) && false === $options['selectpicker']) {
            return;
        }

        // expanded selects are rendered as checkboxes and using the selectpicker
        // would display an empty dropdown
        if (isset($options['expanded']) && true === $options['expanded']) {
            return;
        }

        $extendedOptions = ['class' => 'selectpicker'];

        if ($options['multiple']) {
            $extendedOptions['size'] = 1;
        }

        if (false !== $options['width']) {
            $extendedOptions['data-width'] = $options['width'];
        }

        if (false === $options['search']) {
            $extendedOptions['data-disable-search'] = 1;
        }

        // there is a very weird logic in vendor/symfony/twig-bridge/Resources/views/Form/form_div_layout.html.twig
        // in block "block choice_widget_collapsed" that resets "{% set required = false %}", so we fake it into the select
        if (true === $options['required'] && \is_array($options['attr']) && (!\array_key_exists('size', $options['attr']) || $options['attr']['size'] <= 1)) {
            $extendedOptions['required'] = 'required';
            $extendedOptions['placeholder'] = '';
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], $extendedOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['selectpicker']);
        $resolver->setAllowedTypes('selectpicker', 'boolean');
        $resolver->setDefault('selectpicker', true);

        $resolver->setDefined(['width']);
        $resolver->setAllowedTypes('width', ['string', 'boolean']);
        $resolver->setDefault('width', '100%');

        $resolver->setDefined(['search']);
        $resolver->setAllowedTypes('search', 'boolean');
        $resolver->setDefault('search', true);
    }
}
