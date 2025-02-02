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
 * Custom form field type to select a year via picker and select previous and next year.
 * Always falls back to the current year if none or an invalid date is given.
 * @extends AbstractType<\DateTimeInterface|null>
 */
final class YearPickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => false,
            'format' => DateType::HTML5_FORMAT,
            'start_date' => new \DateTimeImmutable(),
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var \DateTimeInterface|null $date */
        $date = $form->getData();

        if ($date === null) {
            /** @var \DateTimeImmutable $date */
            $date = $options['start_date'];
        }

        $date = \DateTimeImmutable::createFromInterface($date);

        $range = [];
        $start = new \DateTimeImmutable();
        $start = $start->setDate((int) $start->format('Y'), (int) $date->format('m'), (int) $date->format('d'));
        for ($i = 0; $i < 6; $i++) {
            $range[] = $start->modify('-' . $i . ' year');
        }

        $view->vars['year'] = $date;
        $view->vars['range'] = $range;
        $view->vars['previousYear'] = $date->modify('-1 year');
        $view->vars['nextYear'] = $date->modify('+1 year');
    }

    public function getParent(): string
    {
        return DateType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'yearpicker';
    }
}
