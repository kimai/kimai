<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\DataTransformer\DurationStringToSecondsTransformer;
use App\Validator\Constraints\Duration as DurationConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to handle duration strings.
 */
class DurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.duration',
            'constraints' => [new DurationConstraint()],
            'preset_hours' => null,
            'preset_minutes' => null,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['preset_hours'] === null || $options['preset_minutes'] === null) {
            return;
        }

        $intervalMinutes = (int) $options['preset_minutes'];
        $maxHours = (int) $options['preset_hours'];

        if ($intervalMinutes < 1 || $maxHours < 1) {
            return;
        }

        $maxMinutes = $maxHours * 60;
        $presets = [];

        for ($minutes = $intervalMinutes; $minutes <= $maxMinutes; $minutes += $intervalMinutes) {
            $h = (int) ($minutes / 60);
            $m = $minutes % 60;
            $interval = new \DateInterval('PT' . $h . 'H' . $m . 'M');
            $presets[] = $interval->format('%h:%I');
        }

        $view->vars['duration_presets'] = $presets;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new DurationStringToSecondsTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}
