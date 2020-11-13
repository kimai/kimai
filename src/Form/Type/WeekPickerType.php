<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Utils\MomentFormatConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a week via picker and select previous and next week.
 *
 * Always falls back to the current week if none or an invalid date is given.
 */
final class WeekPickerType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => false,
            'format' => DateType::HTML5_FORMAT,
            'start_date' => new \DateTime(),
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var \DateTime|null $date */
        $date = $form->getData();

        if (null === $date) {
            $date = $options['start_date'];
        }

        $view->vars['week'] = $date;
        $view->vars['previousWeek'] = (clone $date)->modify('-1 week');
        $view->vars['nextWeek'] = (clone $date)->modify('+1 week');
        $view->vars['momentFormat'] = (new MomentFormatConverter())->convert($options['format']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'weekpicker';
    }
}
