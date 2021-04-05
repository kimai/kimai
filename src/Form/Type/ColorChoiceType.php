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
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColorChoiceType extends AbstractType implements DataTransformerInterface
{
    public const DEFAULT_COLOR = Constants::DEFAULT_COLOR;

    private $systemConfiguration;
    /**
     * @var bool|null
     */
    private $limitedColors;

    public function __construct(SystemConfiguration $systemConfiguration)
    {
        $this->systemConfiguration = $systemConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer($this);
    }

    private function isLimitedColors(): bool
    {
        if (null === $this->limitedColors) {
            $this->limitedColors = $this->systemConfiguration->isThemeColorsLimited();
        }

        return $this->limitedColors;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $options = [
            'documentation' => [
                'type' => 'string',
                'description' => sprintf('The hexadecimal color code (default: %s)', self::DEFAULT_COLOR),
            ],
            'label' => 'label.color',
            'empty_data' => null,
        ];

        if ($this->isLimitedColors()) {
            $choices = [];
            foreach ($this->systemConfiguration->getThemeColorChoices() as $name => $color) {
                $choices[$name] = $color;
            }
            $options['choices'] = $choices;
            $options['search'] = false;
            $options['attr']['data-renderer'] = 'color';
        }

        $resolver->setDefaults($options);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data)
    {
        if (empty($data) && !$this->isLimitedColors()) {
            return self::DEFAULT_COLOR;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if (null === $data && !$this->isLimitedColors()) {
            return self::DEFAULT_COLOR;
        }

        return null === $data ? null : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if ($this->isLimitedColors()) {
            return ChoiceType::class;
        }

        return ColorType::class;
    }
}
