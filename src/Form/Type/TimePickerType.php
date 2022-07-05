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
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimePickerType extends AbstractType
{
    public function __construct(private LocaleService $localeService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $format = $this->localeService->getTimeFormat(\Locale::getDefault());
        $converter = new FormFormatConverter();
        $formFormat = $converter->convert($format);
        $pattern = $converter->convertToPattern($formFormat);

        $resolver->setDefaults([
            'input' => 'string',
            'format' => $formFormat,
            'placeholder' => $formFormat, // $format
            'time_increment' => null,
            'model_timezone' => date_default_timezone_get(),
            'view_timezone' => date_default_timezone_get(),
            'attr' => [
                'pattern' => $pattern
            ],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($data) use ($options) {
                    if ($data === null) {
                        return null;
                    }

                    // missing catch on purpose, will be auto-converted to a TransformationException
                    return $data->format($options['format']);
                },
                function ($data) use ($options) {
                    if ($data === null) {
                        return null;
                    }

                    // missing catch on purpose, will be auto-converted to a TransformationException
                    $dt = \DateTime::createFromFormat($options['format'], $data, new \DateTimeZone($options['model_timezone']));

                    if ($dt === false) {
                        throw new TransformationFailedException('Invalid time format');
                    }

                    return $dt;
                }
            )
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['time_increment'] !== null && $options['time_increment'] >= 1) {
            $intervalMinutes = (int) $options['time_increment'];

            $maxMinutes = 24 * 60 - $intervalMinutes;

            $date = new \DateTime('now', new \DateTimeZone($options['model_timezone']));
            $date->setTime(0, 0, 0);

            $presets[] = $date->format($options['format']);

            for ($minutes = $intervalMinutes; $minutes <= $maxMinutes; $minutes += $intervalMinutes) {
                $date->modify('+' . $intervalMinutes . ' minutes');

                $presets[] = $date->format($options['format']);
            }

            $view->vars['duration_presets'] = $presets;
        }
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
