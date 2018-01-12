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

use App\Form\Type\PageSizeType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Repository\Query\CustomerQuery;

/**
 * Defines the base form used for all toolbars with pageSizes.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class PagedToolbarForm extends AbstractToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var CustomerQuery $query */
        $query = $options['data'];

        $builder
            ->add('pageSize', PageSizeType::class, [
                'required' => false,
            ])
        ;
    }
}
