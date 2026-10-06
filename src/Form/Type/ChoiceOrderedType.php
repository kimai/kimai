<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A multiple choice type, which keeps the order of the selected choices.
 */
final class ChoiceOrderedType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multiple' => true,
            'order' => true,
        ]);
        $resolver->setAllowedValues('multiple', true);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // the ChoiceType merges the submitted choices into the existing data, which keeps the old order
        $ordered = [];

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use (&$ordered): void {
                $ordered = [];
                $data = $event->getData();
                if (\is_array($data)) {
                    foreach ($data as $value) {
                        // choice values are strings, the ChoiceType already rejects all other types
                        if (\is_string($value) || \is_int($value)) {
                            $ordered[] = (string) $value;
                        }
                    }
                }
                $ordered = array_values(array_unique($ordered));
            }
        );
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use (&$ordered): void {
                $choiceList = $event->getForm()->getConfig()->getAttribute('choice_list');
                if ($choiceList instanceof ChoiceListInterface) {
                    $event->setData(array_values($choiceList->getChoicesForValues($ordered)));
                }
            }
        );
    }

    /**
     * The select widget reads the selected options in the order they appear in the HTML,
     * which is the order of the choice list. Pass the saved order to the widget, otherwise
     * editing would show (and save) the selected choices in the wrong order.
     *
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $values = $view->vars['value'];
        if (!\is_array($values) || \count($values) === 0) {
            return;
        }

        $view->vars['attr']['data-items'] = json_encode(array_values($values));
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
