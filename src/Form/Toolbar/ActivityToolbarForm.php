<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\ActivityQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the activities.
 */
final class ActivityToolbarForm extends AbstractType
{
    use ToolbarFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $newOptions = [];
        if ($options['ignore_date'] === true) {
            $newOptions['ignore_date'] = true;
        }

        $this->addSearchTermInputField($builder);
        $this->addCustomerMultiChoice($builder, $newOptions, true);
        $this->addProjectMultiChoice($builder, $newOptions, true, false);
        $builder->add('globalsOnly', ChoiceType::class, [
            'choices' => [
                'yes' => 1,
                'no' => 0,
            ],
            'search' => false,
            'placeholder' => null,
            'required' => false,
            'label' => 'globalsOnly',
        ]);
        $this->addVisibilityChoice($builder);
        $this->addPageSizeChoice($builder);
        $this->addHiddenPagination($builder);
        $this->addOrder($builder);
        $this->addOrderBy($builder, ActivityQuery::ACTIVITY_ORDER_ALLOWED);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivityQuery::class,
            'csrf_protection' => false,
            'ignore_date' => true,
        ]);
    }
}
