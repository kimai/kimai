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
use App\Form\Type\ExportSummaryColumnsType;
use App\Form\Type\LanguageType;
use App\Form\Type\PdfFontType;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * TODO rename with 3.0 to ExportTemplateForm
 */
class ExportTemplateSpreadsheetForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, ['label' => 'name']);
        $builder->add('renderer', ExportRendererType::class, ['label' => 'type']);
        $builder->add('language', LanguageType::class, ['required' => false]);
        $builder->add('columns', ExportColumnsType::class, ['required' => true]);

        $builder->add('separator', ChoiceType::class, [
            'choices' => ['Comma (,)' => ',', 'Semicolon (;)' => ';'],
            'row_attr' => ['data-type' => 'csv'],
            'required' => true,
        ]);

        $builder->add('name', TextType::class, [
            'label' => 'title',
            'constraints' => [new Length(max: 100)],
            'attr' => ['maxlength' => 100],
            'row_attr' => ['data-type' => 'pdf'],
            'required' => false,
        ]);

        $builder->add('summaryColumns', ExportSummaryColumnsType::class, [
            'row_attr' => ['data-type' => 'pdf'],
            'required' => false,
        ]);

        $builder->add('font', PdfFontType::class, [
            'row_attr' => ['data-type' => 'pdf'],
            'required' => false,
        ]);

        $builder->add('pageSize', ChoiceType::class, [
            'label' => 'pageSize',
            'choices' => [
                'A4' => 'A4',
                'A5' => 'A5',
                'A6' => 'A6',
                'Legal' => 'Legal',
                'Letter' => 'Letter',
            ],
            'row_attr' => ['data-type' => 'pdf'],
            'required' => false,
        ]);

        $builder->add('orientation', ChoiceType::class, [
            'label' => 'orientation',
            'choices' => [
                'portrait' => 'portrait',
                'landscape' => 'landscape',
            ],
            'row_attr' => ['data-type' => 'pdf'],
            'required' => false,
        ]);

        $builder->add('availableForAll', YesNoType::class, [
            'label' => 'user_access_all',
            'required' => false,
        ]);
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
