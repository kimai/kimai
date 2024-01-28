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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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

                if ($reverseTransform instanceof \DateTimeInterface && $options['force_time'] !== null) {
                    if ($options['force_time'] === 'start') {
                        $reverseTransform = \DateTime::createFromInterface($reverseTransform);
                        $reverseTransform = $reverseTransform->setTime(0, 0, 0);
                    } elseif ($options['force_time'] === 'end') {
                        $reverseTransform = \DateTime::createFromInterface($reverseTransform);
                        $reverseTransform = $reverseTransform->setTime(23, 59, 59);
                    } elseif (\is_string($options['force_time'])) {
                        $reverseTransform = \DateTime::createFromInterface($reverseTransform);
                        $reverseTransform = $reverseTransform->modify($options['force_time']);
                    }
                }

                return $reverseTransform;
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['min_day'] !== null) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'min' => $options['min_day'],
            ]);
        }
        if ($options['max_day'] !== null) {
            $view->vars['attr'] = array_merge($view->vars['attr'], [
                'max' => $options['max_day'],
            ]);
        }
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
            'force_time' => null, // one of: string (start, end) or a string to as argument for DateTime->modify() or null
            'min_day' => null,
            'max_day' => null,
        ]);
    }

    public function getParent(): string
    {
        return DateType::class;
    }
}
