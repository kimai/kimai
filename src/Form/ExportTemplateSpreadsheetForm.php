<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\ExportTemplate;
use App\Form\Type\ExportColumnsType;
use App\Form\Type\ExportRendererType;
use App\Form\Type\LanguageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportTemplateSpreadsheetForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class);
        $builder->add('renderer', ExportRendererType::class, ['label' => 'type']);
        $builder->add('language', LanguageType::class, ['required' => false]);
        $builder->add('columns', ExportColumnsType::class, ['required' => true]);
        $builder->add('separator', ChoiceType::class, ['choices' => ['Comma (,)' => ',', 'Semicolon (;)' => ';'], 'required' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExportTemplate::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'export_template_create',
            'attr' => [
                'data-form-event' => 'kimai.exportTemplate'
            ],
        ]);
    }
}
