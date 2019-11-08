<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Timesheet\RoundingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the timesheet mode.
 */
class RoundingModeType extends AbstractType
{
    /**
     * @var RoundingService
     */
    private $service;

    public function __construct(RoundingService $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];

        foreach ($this->service->getRoundingModes() as $mode) {
            $id = $mode->getId();
            $choices[ucfirst($id)] = $id;
        }

        $resolver->setDefaults([
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
