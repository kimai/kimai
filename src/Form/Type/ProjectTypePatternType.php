<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\DataTransformer\StringToArrayTransformer;
use App\Form\Helper\ProjectHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Select the pattern that will be used when rendering a project select.
 */
final class ProjectTypePatternType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ReversedTransformer(new StringToArrayTransformer(ProjectHelper::PATTERN_SPACER)), true);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $name = $this->translator->trans('name');
        $comment = $this->translator->trans('description');
        $orderNumber = $this->translator->trans('orderNumber');
        $projectStart = $this->translator->trans('project_start');
        $projectEnd = $this->translator->trans('project_end');
        $customer = $this->translator->trans('customer');
        $number = $this->translator->trans('project_number');

        $resolver->setDefaults([
            'label' => 'choice_pattern',
            'multiple' => true,
            'choices' => [
                $number => ProjectHelper::PATTERN_NUMBER,
                $orderNumber => ProjectHelper::PATTERN_ORDERNUMBER,
                $name => ProjectHelper::PATTERN_NAME,
                $comment => ProjectHelper::PATTERN_COMMENT,
                $customer => ProjectHelper::PATTERN_CUSTOMER,
                $projectStart . '-' . $projectEnd => ProjectHelper::PATTERN_DATERANGE,
            ]
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
