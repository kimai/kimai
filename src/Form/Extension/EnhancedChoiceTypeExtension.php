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
    /**
     * @deprecated since 1.7 will be removed with 2.0
     */
    public const TYPE_SELECTPICKER = 'selectpicker';

    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class, ChoiceType::class];
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['selectpicker']) && false === $options['selectpicker']) {
            return;
        }

        // expanded selects are rendered as checkboxes and using the selectpicker
        // would display an empty dropdown
        if (isset($options['expanded']) && true === $options['expanded']) {
            return;
        }

        if (!isset($view->vars['attr'])) {
            $view->vars['attr'] = [];
        }

        $extendedOptions = ['class' => 'selectpicker', 'data-width' => '100%'];
        if (!$options['search']) {
            $extendedOptions['data-minimum-results-for-search'] = 'Infinity';
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], $extendedOptions);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['selectpicker']);
        $resolver->setAllowedTypes('selectpicker', 'boolean');
        $resolver->setDefault('selectpicker', true);

        $resolver->setDefined(['search']);
        $resolver->setAllowedTypes('search', 'boolean');
        $resolver->setDefault('search', true);
    }
}
