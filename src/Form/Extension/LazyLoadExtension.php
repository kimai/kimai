<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Form\ChoiceList\LazyChoiceLoader;
use App\Form\ChoiceList\LazyChoiceTypeInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Option "lazy_load": render only the selected choice, search the others through the API.
 */
final class LazyLoadExtension extends AbstractTypeExtension
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$options['lazy_load']) {
            return;
        }

        $type = $form->getConfig()->getType()->getInnerType();
        if (!$type instanceof LazyChoiceTypeInterface) {
            throw new \InvalidArgumentException('Option "lazy_load" is not supported by form type ' . $type::class);
        }

        $api = $type->getLazyLoadApi($options);
        $params = $api['params'];
        if (($parent = $this->getParentId($api['parent'])) !== null) {
            $params[$api['parent_param']] = $parent;
        }

        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-lazy-url' => $this->urlGenerator->generate($api['route'], $params),
            'data-lazy-parent' => $api['parent_param'],
            'data-lazy-all-without-parent' => $api['all_without_parent'] ? '1' : '0',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('lazy_load', false);
        $resolver->setAllowedTypes('lazy_load', 'bool');
        $resolver->addNormalizer('choice_loader', function (Options $options, ?ChoiceLoaderInterface $loader): ?ChoiceLoaderInterface {
            if ($loader === null || !$options['lazy_load']) {
                return $loader;
            }

            return new LazyChoiceLoader($loader);
        });
    }

    /**
     * @param mixed $parent an entity or its ID, e.g. the "customers" option of a project select
     */
    private function getParentId(mixed $parent): ?int
    {
        if (\is_array($parent)) {
            if (\count($parent) > 1) {
                throw new \InvalidArgumentException('Option "lazy_load" supports only one parent');
            }
            $parent = array_values($parent)[0] ?? null;
        }

        if (\is_object($parent) && method_exists($parent, 'getId')) {
            $parent = $parent->getId();
        }

        return \is_int($parent) || (\is_string($parent) && ctype_digit($parent)) ? (int) $parent : null;
    }
}
