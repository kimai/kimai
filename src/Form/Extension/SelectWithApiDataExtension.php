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
class SelectWithApiDataExtension extends AbstractTypeExtension
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @param UrlGeneratorInterface $router
     */
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class];
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!isset($options['api_data'])) {
            return;
        }

        $apiData = $options['api_data'];

        if (!\is_array($apiData)) {
            throw new \InvalidArgumentException('Option "api_data" must be an array for form "' . $form->getName() . '"');
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

        $formPrefix = $form->getParent()->getName();
        if (!empty($formPrefix)) {
            $formPrefix .= '_';
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-related-select' => $formPrefix . $apiData['select'],
            'data-api-url' => $this->router->generate($apiData['route'], $apiData['route_params']),
        ]);

        if (isset($apiData['empty_route_params'])) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'data-empty-url' => $this->router->generate($apiData['route'], $apiData['empty_route_params']),
            ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['api_data']);
        $resolver->setAllowedTypes('api_data', 'array');
    }
}
