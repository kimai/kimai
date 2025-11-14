<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use App\Form\ProjectEditForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProjectApiEditForm extends AbstractType
{
    public function getParent(): string
    {
        return ProjectEditForm::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // overwritten, so the docs show these fields
        $resolver->setDefaults([
            'include_budget' => true,
            'include_time' => true,
        ]);
    }
}
