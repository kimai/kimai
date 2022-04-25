<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Select the pattern that will be used for calendar entry titles.
 */
class CalendarTitlePatternType extends AbstractType
{
    public const PATTERN_CUSTOMER = '{customer}';
    public const PATTERN_PROJECT = '{project}';
    public const PATTERN_ACTIVITY = '{activity}';
    public const PATTERN_DESCRIPTION = '{description}';
    public const SPACER = ' - ';

    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $customer = $this->translator->trans('label.customer');
        $project = $this->translator->trans('label.project');
        $activity = $this->translator->trans('label.activity');
        $description = $this->translator->trans('label.description');
        $spacer = CustomerType::SPACER;

        $resolver->setDefaults([
            'label' => 'label.choice_pattern',
            'choices' => [
                $activity => CalendarTitlePatternType::PATTERN_ACTIVITY,
                $project => CalendarTitlePatternType::PATTERN_PROJECT,
                $customer => CalendarTitlePatternType::PATTERN_CUSTOMER,
                $description => CalendarTitlePatternType::PATTERN_DESCRIPTION,
            ]
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
