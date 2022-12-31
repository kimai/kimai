<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\Helper\CustomerHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Select the pattern that will be used when rendering a custom select.
 */
final class CustomerTypePatternType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $name = $this->translator->trans('name');
        $company = $this->translator->trans('company');
        $number = $this->translator->trans('number');
        $comment = $this->translator->trans('description');
        $spacer = CustomerHelper::SPACER;

        $resolver->setDefaults([
            'label' => 'choice_pattern',
            'choices' => [
                $name => CustomerHelper::PATTERN_NAME,
                $company => CustomerHelper::PATTERN_COMPANY,
                $number => CustomerHelper::PATTERN_NUMBER,
                $comment => CustomerHelper::PATTERN_COMMENT,
                $name . $spacer . $company => CustomerHelper::PATTERN_NAME . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_COMPANY,
                $name . $spacer . $number => CustomerHelper::PATTERN_NAME . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_NUMBER,
                $name . $spacer . $comment => CustomerHelper::PATTERN_NAME . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_COMMENT,
                $number . $spacer . $name => CustomerHelper::PATTERN_NUMBER . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_NAME,
                $number . $spacer . $company => CustomerHelper::PATTERN_NUMBER . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_COMPANY,
                $number . $spacer . $comment => CustomerHelper::PATTERN_NUMBER . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_COMMENT,
                $company . $spacer . $comment => CustomerHelper::PATTERN_COMPANY . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_COMMENT,
            ]
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
