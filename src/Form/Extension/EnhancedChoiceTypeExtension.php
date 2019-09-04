<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Configuration\ThemeConfiguration;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Converts normal select boxes into javascript enhanced versions.
 */
class EnhancedChoiceTypeExtension extends AbstractTypeExtension
{
    public const TYPE_SELECTPICKER = 'selectpicker';

    /**
     * @var string|null
     */
    protected $type = null;

    public function __construct(ThemeConfiguration $configuration)
    {
        $this->type = $configuration->getSelectPicker();
    }

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
        if ($this->type !== self::TYPE_SELECTPICKER) {
            return;
        }

        if (isset($options['selectpicker']) && false === $options['selectpicker']) {
            return;
        }

        if (!isset($view->vars['attr'])) {
            $view->vars['attr'] = [];
        }

        $view->vars['attr'] = array_merge(
            $view->vars['attr'],
            ['class' => 'selectpicker', 'data-live-search' => true, 'data-width' => '100%']
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['selectpicker']);
        $resolver->setAllowedTypes('selectpicker', 'boolean');
        $resolver->setDefault('selectpicker', true);
    }
}
