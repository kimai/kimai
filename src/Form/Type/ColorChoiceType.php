<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Constants;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorChoiceType extends AbstractType implements DataTransformerInterface
{
    public function __construct(private SystemConfiguration $systemConfiguration)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $options = [
            'documentation' => [
                'type' => 'string',
                'description' => sprintf('The hexadecimal color code (default: %s)', Constants::DEFAULT_COLOR),
            ],
            'label' => 'label.color',
            'empty_data' => null,
        ];

        $choices = [];
        $colors = $this->convertStringToColorArray($this->systemConfiguration->getThemeColorChoices());

        foreach ($colors as $name => $color) {
            $choices[$name] = $color;
        }

        $options['choices'] = $choices;
        $options['search'] = false;
        $options['attr']['data-renderer'] = 'color';

        $resolver->setDefaults($options);
    }

    private function convertStringToColorArray(string $config): array
    {
        $config = explode(',', $config);

        $colors = [];
        foreach ($config as $item) {
            if (empty($item)) {
                continue;
            }
            $item = explode('|', $item);
            $key = $item[0];
            $value = $key;

            if (\count($item) > 1) {
                $value = $item[1];
            }

            if (empty($key)) {
                $key = $value;
            }

            if ($value === Constants::DEFAULT_COLOR) {
                continue;
            }

            $colors[$key] = $value;
        }

        return array_unique($colors);
    }

    /**
     * {@inheritdoc}
     */
    public function transform(mixed $data): mixed
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform(mixed $value): mixed
    {
        return $value;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
