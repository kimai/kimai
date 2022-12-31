<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\Helper\ProjectHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Select the pattern that will be used when rendering a project select.
 */
final class ProjectTypePatternType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $name = $this->translator->trans('name');
        $comment = $this->translator->trans('description');
        $orderNumber = $this->translator->trans('orderNumber');
        $projectStart = $this->translator->trans('project_start');
        $projectEnd = $this->translator->trans('project_end');
        $customer = $this->translator->trans('customer');

        $spacer = ProjectHelper::SPACER;

        $resolver->setDefaults([
            'label' => 'choice_pattern',
            'choices' => [
                $name => ProjectHelper::PATTERN_NAME,
                $comment => ProjectHelper::PATTERN_COMMENT,
                $name . $spacer . $customer => ProjectHelper::PATTERN_NAME . ProjectHelper::PATTERN_SPACER . ProjectHelper::PATTERN_CUSTOMER,
                $name . $spacer . $orderNumber => ProjectHelper::PATTERN_NAME . ProjectHelper::PATTERN_SPACER . ProjectHelper::PATTERN_ORDERNUMBER,
                $name . $spacer . $comment => ProjectHelper::PATTERN_NAME . ProjectHelper::PATTERN_SPACER . ProjectHelper::PATTERN_COMMENT,
                $name . $spacer . $projectStart . '-' . $projectEnd => ProjectHelper::PATTERN_NAME . ProjectHelper::PATTERN_SPACER . ProjectHelper::PATTERN_DATERANGE,
            ]
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
