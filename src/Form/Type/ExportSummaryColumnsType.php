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

final class ExportSummaryColumnsType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $columns = [
            $this->translator->trans('duration') . ' (1:30)' => 'duration',
            $this->translator->trans('duration') . ' (1.5)' => 'duration_decimal',
            'rate' => 'rate',
            'internalRate' => 'internal_rate',
            \sprintf('%s (%s)', $this->translator->trans('remaining_budget'), $this->translator->trans('budget')) => 'project_budget_money',
            \sprintf('%s (%s)', $this->translator->trans('remaining_budget'), $this->translator->trans('timeBudget')) => 'project_budget_time',
        ];

        $resolver->setDefaults([
            'choices' => $columns,
            'label' => 'export.summary',
            'multiple' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
