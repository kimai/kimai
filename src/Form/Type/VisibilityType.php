<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Repository\Query\VisibilityQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select a visibility.
 */
class VisibilityType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.visible',
            'choices' => [
                'both' => VisibilityQuery::SHOW_BOTH,
                'yes' => VisibilityQuery::SHOW_VISIBLE,
                'no' => VisibilityQuery::SHOW_HIDDEN,
            ],
            //'attr' => ['class' => 'selectpicker', 'data-live-search' => false, 'data-width' => '100%']
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
