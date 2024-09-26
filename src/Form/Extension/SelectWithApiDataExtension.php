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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Support Remote-API calls for Entity select-boxes.
 */
final class SelectWithApiDataExtension extends AbstractTypeExtension
{
    public function __construct(private readonly UrlGeneratorInterface $router)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!isset($options['api_data'])) {
            return;
        }

        $apiData = $options['api_data'];

        if (!\is_array($apiData)) {
            throw new \InvalidArgumentException('Option "api_data" must be an array for form "' . $form->getName() . '"');
        }

        if (isset($apiData['create'])) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-create' => $this->router->generate($apiData['create']),
            ]);
        }

        if (!isset($apiData['select'])) {
            return;
        }

        if (!isset($apiData['route'])) {
            throw new \InvalidArgumentException('Missing "route" option for "api_data" option for form "' . $form->getName() . '"');
        }

        if (!isset($apiData['route_params'])) {
            $apiData['route_params'] = [];
        }

        $formPrefixes = [];
        $parent = $form->getParent();
        do {
            $formPrefixes[] = $parent->getName();
        } while (($parent = $parent?->getParent()) !== null);

        $formPrefix = implode('_', array_reverse($formPrefixes));
        $formField = $apiData['select'];

        // forms with prefix (like toolbar & search) would result in a wrong field name "_foo" instead of "foo"
        if ($formPrefix !== '') {
            $formField = $formPrefix . '_' . $apiData['select'];
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-form-prefix' => $formPrefix,
            'data-related-select' => $formField,
            'data-api-url' => $this->router->generate($apiData['route'], $apiData['route_params']),
        ]);

        if (isset($apiData['empty_route_params'])) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-empty-url' => $this->router->generate($apiData['route'], $apiData['empty_route_params']),
            ]);
        }

        if (isset($apiData['reload'])) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-reload' => $this->router->generate($apiData['reload']),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['api_data']);
        $resolver->setAllowedTypes('api_data', 'array');
    }
}
