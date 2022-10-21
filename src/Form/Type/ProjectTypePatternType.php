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
class ProjectTypePatternType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $name = $this->translator->trans('label.name');
        $comment = $this->translator->trans('label.description');
        $orderNumber = $this->translator->trans('label.orderNumber');
        $projectStart = $this->translator->trans('label.project_start');
        $projectEnd = $this->translator->trans('label.project_end');
        $customer = $this->translator->trans('label.customer');

        $spacer = ProjectHelper::SPACER;

        $resolver->setDefaults([
            'label' => 'label.choice_pattern',
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
