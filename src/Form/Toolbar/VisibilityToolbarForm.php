<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Type\VisibilityType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Defines the form used for filtering entities with a "visibility" field.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class VisibilityToolbarForm extends PagedToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('visibility', VisibilityType::class, [
                'required' => false,
            ])
        ;
    }
}
