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
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to select the language.
 */
class LanguageType extends AbstractType
{
    /**
     * @var string[]
     */
    private $locales = [];

    /**
     * @param array|string $locales
     */
    public function __construct($locales)
    {
        if (!is_array($locales)) {
            $locales = explode('|', $locales);
        }

        $this->locales = $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        foreach ($this->locales as $key) {
            $name = ucfirst(Intl::getLocaleBundle()->getLocaleName($key, $key));
            $choices[$name] = $key;
        }

        $resolver->setDefaults([
            'choices' => $choices
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
