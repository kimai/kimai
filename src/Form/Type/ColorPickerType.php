<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Constants;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ColorPickerType extends AbstractType implements DataTransformerInterface
{
    public const DEFAULT_COLOR = Constants::DEFAULT_COLOR;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'documentation' => [
                'type' => 'string',
                'description' => sprintf('The hexadecimal color code (default: %s)', self::DEFAULT_COLOR),
            ],
            'label' => 'color',
            'empty_data' => null,
        ]);
    }

    public function transform(mixed $data): mixed
    {
        if (empty($data)) {
            return self::DEFAULT_COLOR;
        }

        return $data;
    }

    public function reverseTransform(mixed $value): mixed
    {
        return null === $value ? self::DEFAULT_COLOR : $value;
    }

    public function getParent(): string
    {
        return ColorType::class;
    }
}
