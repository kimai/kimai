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
use App\Utils\JavascriptFormatConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TimePickerType extends AbstractType
{
    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'input' => 'string',
            'locale' => \Locale::getDefault(),
            'model_timezone' => date_default_timezone_get(),
            'view_timezone' => date_default_timezone_get(),
            'block_prefix' => 'time'
        ]);

        $resolver->setDefault('time_format', function (Options $options): string {
            // We used the configured time format via "getTimeFormat()" for entering times before, but it caused issues.
            // So now we only allow two different input types: 12-hour with AM/PM suffix and 24-hour
            return $this->localeService->is24Hour($options['locale']) ? 'HH:mm' : 'h:mm a';
        });

        $resolver->setDefault('format', function (Options $options): string {
            $converter = new FormFormatConverter();

            return $converter->convert($options['time_format']);
        });

        $resolver->setDefault('placeholder', function (Options $options): string {
            return $options['time_format'];
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['format'] = $options['format'];
        $view->vars['time_format'] = $options['time_format'];
        $view->vars['js_format'] = (new JavascriptFormatConverter())->convert($options['time_format']); // @phpstan-ignore argument.type
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($data) use ($options) {
                    if ($data === null) {
                        return null;
                    }

                    // DateTimePickerType
                    if ($options['input'] === 'array' && \is_array($data)) {
                        if (!\array_key_exists('hour', $data) || $data['hour'] === '' || $data['hour'] === null) {
                            return null;
                        }

                        if (!\array_key_exists('minute', $data) || $data['minute'] === '' || $data['minute'] === null) {
                            return null;
                        }

                        $now = new \DateTime('now', new \DateTimeZone($options['model_timezone']));
                        $hour = !is_numeric($data['hour']) ? 0 : (int) $data['hour'];
                        $minute = !is_numeric($data['minute']) ? 0 : (int) $data['minute'];
                        $now->setTime($hour, $minute, 0);
                        $data = $now;
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

                    // DateTimePickerType
                    if ($options['input'] === 'array') {
                        return [
                            'hour' => $dt->format('H'),
                            'minute' => $dt->format('i'),
                        ];
                    }

                    return $dt;
                }
            )
        );
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
