<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\Helper\ActivityHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Select the pattern that will be used when rendering an activity select.
 */
class ActivityTypePatternType extends AbstractType
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
        $spacer = ActivityHelper::SPACER;

        $resolver->setDefaults([
            'label' => 'label.choice_pattern',
            'choices' => [
                $name => ActivityHelper::PATTERN_NAME,
                $comment => ActivityHelper::PATTERN_COMMENT,
                $name . $spacer . $comment => ActivityHelper::PATTERN_NAME . ActivityHelper::PATTERN_SPACER . ActivityHelper::PATTERN_COMMENT,
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
