<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Timesheet\TrackingModeService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the timesheet mode.
 */
class TrackingModeType extends AbstractType
{
    protected $service;

    public function __construct(TrackingModeService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];

        foreach ($this->service->getModes() as $mode) {
            $id = $mode->getId();
            $choices['label.timesheet.mode_' . $id] = $id;
        }

        $resolver->setDefaults([
            'label' => 'label.timesheet.mode',
            'choices' => $choices,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
