<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorPickerType extends AbstractType implements DataTransformerInterface
{
    public const DEFAULT_COLOR = '#d2d6de';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.color',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data)
    {
        if (empty($data)) {
            return self::DEFAULT_COLOR;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        return null === $data ? self::DEFAULT_COLOR : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ColorType::class;
    }
}
