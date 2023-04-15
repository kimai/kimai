<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Repository\Query\UserQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the form used for filtering the user.
 * @extends AbstractType<UserQuery>
 */
final class UserToolbarForm extends AbstractType
{
    use ToolbarFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSearchTermInputField($builder);
        $this->addUserRoleChoice($builder);
        $this->addTeamsChoice($builder, 'searchTeams');
        $this->addVisibilityChoice($builder, 'active');
        $this->addPageSizeChoice($builder);
        $this->addHiddenPagination($builder);
        $this->addOrder($builder);
        $this->addOrderBy($builder, UserQuery::USER_ORDER_ALLOWED);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
