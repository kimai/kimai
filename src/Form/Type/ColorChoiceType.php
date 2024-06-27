<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Utils\Color;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ColorChoiceType extends AbstractType implements DataTransformerInterface
{
    public function __construct(private readonly SystemConfiguration $systemConfiguration)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $options = [
            'documentation' => [
                'type' => 'string',
                'description' => 'The hexadecimal color code (default: auto-calculated by name)',
            ],
            'label' => 'color',
            'empty_data' => null,
            'choice_attr' => function ($color, $name) {
                if ($color === null) {
                    $color = (new Color())->getRandom($name);
                }

                return ['data-color' => $color];
            },
        ];

        $choices = [];
        $colors = $this->systemConfiguration->getThemeColors();

        foreach ($colors as $name => $color) {
            $choices[$name] = $color;
        }

        $options['choices'] = $choices;
        $options['search'] = false;

        $resolver->setDefaults($options);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-renderer' => 'color',
        ]);
    }

    public function transform(mixed $value): mixed
    {
        return $value;
    }

    public function reverseTransform(mixed $value): mixed
    {
        return $value;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
