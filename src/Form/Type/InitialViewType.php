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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Custom form field type to select the initial view, where the user should be redirected to after login.
 */
class InitialViewType extends AbstractType
{
    public const DEFAULT_VIEW = 'timesheet';

    public const ALLOWED_VIEWS = [
        'dashboard' => 'menu.homepage',
        'timesheet' => 'menu.timesheet',
        'calendar' => 'calendar.title',
        'my_profile' => 'profile.title',
        'admin_timesheet' => 'menu.admin_timesheet',
        'invoice' => 'menu.invoice',
        'admin_user' => 'menu.admin_user',
        'admin_customer' => 'menu.admin_customer',
        'admin_project' => 'menu.admin_project',
        'admin_activity' => 'menu.admin_activity',
    ];

    protected const ROUTE_PERMISSION = [
        'dashboard' => 'menu.homepage',
        'timesheet' => 'view_own_timesheet',
        'calendar' => 'view_own_timesheet',
        'my_profile' => 'view_own_profile',
        'admin_timesheet' => 'view_other_timesheet',
        'invoice' => 'view_invoice',
        'admin_user' => 'view_user',
        'admin_customer' => 'view_customer',
        'admin_project' => 'view_project',
        'admin_activity' => 'view_activity',
    ];

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $voter;

    /**
     * @param AuthorizationCheckerInterface $voter
     */
    public function __construct(AuthorizationCheckerInterface $voter)
    {
        $this->voter = $voter;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        foreach (self::ROUTE_PERMISSION as $route => $permission) {
            if ($this->voter->isGranted($permission)) {
                $name = self::ALLOWED_VIEWS[$route];
                $choices[$name] = $route;
            }
        }

        $resolver->setDefaults([
            'required' => true,
            'choices' => $choices,
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
