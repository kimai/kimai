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
use Symfony\Component\OptionsResolver\OptionsResolver;

class APIDateTimeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'documentation' => [
                'type' => 'string',
                'format' => 'date-time',
                'example' => (new \DateTime())->format(BaseApiController::DATE_FORMAT_PHP),
            ],
            'widget' => 'single_text',
            'html5' => true,
            'model_timezone' => date_default_timezone_get(),
            'view_timezone' => date_default_timezone_get(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateTimeType::class;
    }
}
