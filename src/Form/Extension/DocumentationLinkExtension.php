<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds a linked help text with the link to the given documentation
 */
class DocumentationLinkExtension extends AbstractTypeExtension
{
    public function getExtendedType()
    {
        return FormType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['documentation'] = $options['documentation'] ?? '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(array('documentation'));
        $resolver->setDefaults(['documentation' => null]);
    }
}
