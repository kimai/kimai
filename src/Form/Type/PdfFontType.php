<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PdfFontType extends AbstractType
{
    public const AVAILABLE_FONTS = [
        'times', 'serif', 'helvetica', 'sans', 'courier', 'monospace', 'mono', 'sans-serif',
        'dejavusanscondensed', 'dejavusans', 'dejavuserif', 'dejavuserifcondensed', 'dejavusansmono',
        'freesans', 'freeserif', ' freemono',
        // needed for: japanese + hebrew, cyrillic
        'sun-exta', 'unbatang'
        // deactivated for now, maybe later
        // 'ocrb', 'abyssinicasil', 'aboriginalsans', 'jomolhari', 'taiheritagepro', 'aegean', 'aegyptus', 'akkadian',
        // 'quivira', 'lannaalif', 'daibannasilbook', 'garuda', 'khmeros', 'dhyana', 'tharlon',
        // 'padaukbook', 'zawgyi-one', 'ayar', 'taameydavidclm', 'mph2bdamase', 'briyaz', 'lateef',
    ];

    public function configureOptions(OptionsResolver $resolver): void
    {
        $columns = [];
        foreach (self::AVAILABLE_FONTS as $font) {
            $columns[ucfirst($font)] = $font;
        }

        $resolver->setDefaults([
            'choices' => $columns,
            'label' => 'font',
            'multiple' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
