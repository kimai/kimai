<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

final class CommentApiForm extends AbstractType implements ApiFormInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pinned', CheckboxType::class, [
            'required' => false,
            'documentation' => [
                'default' => false,
                'description' => 'Pinned comments always appear first'
            ],
        ]);

        $builder->add('message', TextareaType::class, [
            'label' => false,
            'documentation' => [
                'description' => 'The actual comment (markdown is supported)'
            ],
        ]);
    }
}
