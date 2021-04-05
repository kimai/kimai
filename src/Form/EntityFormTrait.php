<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Type\ColorChoiceType;
use App\Form\Type\DurationType;
use App\Form\Type\MetaFieldsCollectionType;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

trait EntityFormTrait
{
    public function addCommonFields(FormBuilderInterface $builder, array $options): void
    {
        $this->addColor($builder);

        if ($options['include_budget']) {
            $builder
                ->add('budget', MoneyType::class, [
                    'empty_data' => '0.00',
                    'label' => 'label.budget',
                    'required' => false,
                    'currency' => $options['currency'],
                ])
                ->add('timeBudget', DurationType::class, [
                    'empty_data' => 0,
                    'label' => 'label.timeBudget',
                    'icon' => 'clock',
                    'required' => false,
                ])
            ;
        }

        $builder->add('metaFields', MetaFieldsCollectionType::class);

        $builder
            ->add('visible', YesNoType::class, [
                'label' => 'label.visible',
            ]);
    }

    public function addColor(FormBuilderInterface $builder): void
    {
        $builder
            ->add('color', ColorChoiceType::class, [
                'required' => false,
            ])
        ;

        // this code exists only for backward compatibility
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($builder) {
                if (!$builder->get('color')->hasOption('choices')) {
                    return;
                }

                $data = $event->getData();
                $choices = $builder->get('color')->getOption('choices');
                if (\is_object($data) && method_exists($data, 'getColor')) {
                    $color = $data->getColor();
                    if (!empty($color) && array_search($color, $choices) === false) {
                        $choices[$color] = $color;
                    }
                }

                $event->getForm()->add('color', ColorChoiceType::class, [
                    'required' => false,
                    'choices' => $choices,
                ]);
            }
        );
    }

    public function addCreateMore(FormBuilderInterface $builder): void
    {
        $builder->add('create_more', CheckboxType::class, [
            'label' => 'label.create_more',
            'required' => false,
            'mapped' => false,
        ]);
    }
}
