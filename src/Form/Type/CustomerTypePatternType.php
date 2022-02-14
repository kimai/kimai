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
 * Select the pattern that will be used when rendering a custom select.
 */
class CustomerTypePatternType extends AbstractType
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
        $company = $this->translator->trans('label.company');
        $number = $this->translator->trans('label.number');
        $comment = $this->translator->trans('label.description');
        $spacer = CustomerType::SPACER;

        $resolver->setDefaults([
            'label' => 'label.choice_pattern',
            'choices' => [
                $name => CustomerType::PATTERN_NAME,
                $company => CustomerType::PATTERN_COMPANY,
                $number => CustomerType::PATTERN_NUMBER,
                $comment => CustomerType::PATTERN_COMMENT,
                $name . $spacer . $company => CustomerType::PATTERN_NAME . CustomerType::PATTERN_SPACER . CustomerType::PATTERN_COMPANY,
                $name . $spacer . $number => CustomerType::PATTERN_NAME . CustomerType::PATTERN_SPACER . CustomerType::PATTERN_NUMBER,
                $name . $spacer . $comment => CustomerType::PATTERN_NAME . CustomerType::PATTERN_SPACER . CustomerType::PATTERN_COMMENT,
                $number . $spacer . $name => CustomerType::PATTERN_NUMBER . CustomerType::PATTERN_SPACER . CustomerType::PATTERN_NAME,
                $number . $spacer . $company => CustomerType::PATTERN_NUMBER . CustomerType::PATTERN_SPACER . CustomerType::PATTERN_COMPANY,
                $number . $spacer . $comment => CustomerType::PATTERN_NUMBER . CustomerType::PATTERN_SPACER . CustomerType::PATTERN_COMMENT,
                $company . $spacer . $comment => CustomerType::PATTERN_COMPANY . CustomerType::PATTERN_SPACER . CustomerType::PATTERN_COMMENT,
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
