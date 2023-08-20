<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\ColorChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

trait ColorTrait
{
    protected function addColor(FormBuilderInterface $builder, bool $required = false): void
    {
        $builder
            ->add('color', ColorChoiceType::class, [
                'required' => $required,
            ])
        ;

        // this code exists only for backward compatibility
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($required) {
                if (!$event->getForm()->getConfig()->hasOption('choices')) {
                    return;
                }

                $data = $event->getData();
                $choices = $event->getForm()->getConfig()->getOption('choices');
                if (\is_object($data) && method_exists($data, 'getColor')) {
                    $color = $data->getColor();
                    if (!empty($color) && array_search($color, $choices) === false) {
                        $choices[$color] = $color;
                    }
                }

                $event->getForm()->add('color', ColorChoiceType::class, [
                    'required' => $required,
                    'choices' => $choices,
                ]);
            }
        );
    }
}
