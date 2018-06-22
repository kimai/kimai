<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Utils\Duration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Custom form field type to handle a timesheet duration.
 */
class DurationType extends AbstractType
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * DurationType constructor.
     */
    public function __construct()
    {
        $patterns = [
            '[0-9]{1,}',
            '[0-9]{1,}:[0-9]{1,2}:[0-9]{1,2}',
            '[0-9]{1,2}:[0-9]{1,2}',
            '[0-9]{1,}[hmsHMS]{1}',
            '[0-9]{1,}[hmsHMS]{1}[0-9]{1,}[hmsHMS]{1}',
            '[0-9]{1,}[hmsHMS]{1}[0-9]{1,}[hmsHMS]{1}[0-9]{1,}[hmsHMS]{1}',
        ];

        $this->pattern = '/^' . implode('$|^', $patterns) . '$/';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.duration',
            'constraints' => [new Regex(['pattern' => $this->pattern])],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formatter = new Duration();
        $pattern = $this->pattern;

        $builder->addModelTransformer(new CallbackTransformer(
            function ($intToFormat) use ($formatter) {
                try {
                    return $formatter->format($intToFormat, true);
                } catch (\Exception $e) {
                    throw new TransformationFailedException($e->getMessage());
                }
            },
            function ($formatToInt) use ($formatter, $pattern) {
                if (empty($formatToInt)) {
                    return 0;
                }
                if (!preg_match($pattern, $formatToInt)) {
                    throw new TransformationFailedException('Invalid duration format given');
                }
                try {
                    return $formatter->parseDurationString($formatToInt);
                } catch (\Exception $e) {
                    throw new TransformationFailedException($e->getMessage());
                }
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}
