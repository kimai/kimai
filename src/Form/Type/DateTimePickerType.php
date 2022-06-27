<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\API\BaseApiController;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimePickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'documentation' => [
                'type' => 'string',
                'format' => 'date-time',
                'example' => (new \DateTime())->format(BaseApiController::DATE_FORMAT_PHP),
            ],
            'label' => 'label.begin',
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
            'html5' => true,
            'with_seconds' => false,
            'time_increment' => 1,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($options['time_increment'] !== null && $options['time_increment'] >= 1) {
            $view->vars['timePickerIncrement'] = $options['time_increment'] * 60;
        }
    }

    public function getParent(): string
    {
        return DateTimeType::class;
    }
}
