<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a month via picker and select previous and next month.
 *
 * Always falls back to the current month if none or an invalid date is given.
 */
final class MonthPickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => false,
            'format' => DateType::HTML5_FORMAT,
            'start_date' => new \DateTime(),
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var \DateTime|null $date */
        $date = $form->getData();

        if (null === $date) {
            $date = $options['start_date'];
        }

        $range = [];
        $start = new \DateTimeImmutable();
        $end = $start->modify('-2 year');
        $end = $end->setDate((int) $end->format('Y'), 1, 1);
        $i = 1;
        while ($i++ < 36 && $end <= $start) {
            $range[] = $start;
            $start = $start->modify('- 1 month');
        }

        $view->vars['month'] = $date;
        $view->vars['range'] = $range;
        $view->vars['previousMonth'] = (clone $date)->modify('-1 month');
        $view->vars['nextMonth'] = (clone $date)->modify('+1 month');
    }

    public function getParent(): string
    {
        return DateType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'monthpicker';
    }
}
