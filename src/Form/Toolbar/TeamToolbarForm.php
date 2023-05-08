<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\TeamQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<TeamQuery>
 */
final class TeamToolbarForm extends AbstractType
{
    use ToolbarFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUsersChoice($builder);
        $this->addSearchTermInputField($builder);
        $this->addPageSizeChoice($builder);
        $this->addHiddenPagination($builder);
        $this->addOrder($builder);
        $this->addOrderBy($builder, TeamQuery::TEAM_ORDER_ALLOWED);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TeamQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
