<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Configuration\LocaleService;
use App\Utils\FormFormatConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to display the date input fields.
 */
class DatePickerType extends AbstractType
{
    public function __construct(private LocaleService $localeService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($transform) {
                return $transform;
            },
            function ($reverseTransform) use ($options) {
                if ($reverseTransform === null) {
                    return null;
                }

                if ($reverseTransform instanceof \DateTime && $options['force_time']) {
                    if ($options['force_time'] === 'start') {
                        $reverseTransform->setTime(0, 0, 0);
                    } elseif ($options['force_time'] === 'end') {
                        $reverseTransform->setTime(23, 59, 59);
                    }
                }

                return $reverseTransform;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $format = $this->localeService->getDateFormat(\Locale::getDefault());
        $converter = new FormFormatConverter();
        $formFormat = $converter->convert($format);

        $resolver->setDefaults([
            'label' => 'date',
            'widget' => 'single_text',
            'html5' => false,
            'format' => $formFormat,
            'model_timezone' => date_default_timezone_get(),
            'view_timezone' => date_default_timezone_get(),
            'force_time' => null,
        ]);
    }

    public function getParent(): string
    {
        return DateType::class;
    }
}
