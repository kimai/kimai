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

final class TimePickerType extends AbstractType
{
    public function __construct(private LocaleService $localeService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $format = $this->localeService->getTimeFormat(\Locale::getDefault());
        $converter = new FormFormatConverter();
        $formFormat = $converter->convert($format);

        $resolver->setDefaults([
            'input' => 'string',
            'format' => $formFormat,
            'placeholder' => $formFormat, // $format
            'model_timezone' => date_default_timezone_get(),
            'view_timezone' => date_default_timezone_get(),
            'block_prefix' => 'time'
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['format'] = $options['format'];
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
