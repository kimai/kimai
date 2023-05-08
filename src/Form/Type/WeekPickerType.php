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
 * Custom form field type to select a week via picker and select previous and next week.
 * Always falls back to the current week if none or an invalid date is given.
 * @extends AbstractType<\DateTimeInterface|null>
 */
final class WeekPickerType extends AbstractType
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

        if (null === $date) {
            /** @var \DateTimeImmutable $date */
            $date = $options['start_date'];
        }

        $date = \DateTime::createFromInterface($date);

        $view->vars['week'] = $date;
        $view->vars['previousWeek'] = (clone $date)->modify('-1 week');
        $view->vars['nextWeek'] = (clone $date)->modify('+1 week');
    }

    public function getParent(): string
    {
        return DateType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'weekpicker';
    }
}
