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

final class HelpTranslationDomainExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['help_translation_domain'] = $options['help_translation_domain'] ?? null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['help_translation_domain']);
        $resolver->setAllowedTypes('help_translation_domain', ['string', 'null']);
        $resolver->setDefault('help_translation_domain', null);
    }
}
