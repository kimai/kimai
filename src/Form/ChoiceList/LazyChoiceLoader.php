<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\ChoiceList;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Lists only selected and submitted choices, the decorated loader validates submitted values.
 * Like the "choice_lazy" option of Symfony 7.2.
 */
final class LazyChoiceLoader implements ChoiceLoaderInterface
{
    private ?ChoiceListInterface $choiceList = null;

    public function __construct(private readonly ChoiceLoaderInterface $loader)
    {
    }

    public function loadChoiceList(?callable $value = null): ChoiceListInterface
    {
        return $this->choiceList ??= new ArrayChoiceList([], $value);
    }

    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        $choices = $this->loader->loadChoicesForValues($values, $value);
        $this->choiceList = new ArrayChoiceList($choices, $value);

        return $choices;
    }

    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        $values = $this->loader->loadValuesForChoices($choices, $value);

        // the choices are the form data
        $this->choiceList = new ArrayChoiceList(array_filter($choices), $value);

        return $values;
    }
}
