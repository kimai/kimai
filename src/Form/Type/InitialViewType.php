<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\User;
use App\Reporting\ReportingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Custom form field type to select the initial view, where the user should be redirected to after login.
 */
class InitialViewType extends AbstractType
{
    public const DEFAULT_VIEW = 'timesheet';

    private const ALLOWED_VIEWS = [
        'dashboard' => 'menu.homepage',
        'timesheet' => 'menu.timesheet',
        'calendar' => 'calendar',
        'quick_entry' => 'quick_entry.title',
        'my_profile' => 'profile.title',
        'admin_timesheet' => 'menu.admin_timesheet',
        'invoice' => 'invoices',
        'admin_user' => 'users',
        'admin_customer' => 'customers',
        'admin_project' => 'projects',
        'admin_activity' => 'activities',
    ];

    private const ROUTE_PERMISSION = [
        'dashboard' => '',
        'timesheet' => 'view_own_timesheet',
        'calendar' => 'view_own_timesheet',
        'my_profile' => 'view_own_profile',
        'admin_timesheet' => 'view_other_timesheet',
        'invoice' => 'view_invoice',
        'admin_user' => 'view_user',
        'admin_customer' => 'view_customer',
        'admin_project' => 'view_project',
        'admin_activity' => 'view_activity',
        'quick_entry' => 'view_own_timesheet',
    ];

    private $voter;
    private $reportingService;
    private $translator;

    public function __construct(AuthorizationCheckerInterface $voter, ReportingService $reportingService, TranslatorInterface $translator)
    {
        $this->voter = $voter;
        $this->reportingService = $reportingService;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('required', true);
        $resolver->setDefault('choices', function (Options $options) {
            $choices = [];
            foreach (self::ROUTE_PERMISSION as $route => $permission) {
                if (empty($permission) || $this->voter->isGranted($permission)) {
                    $name = self::ALLOWED_VIEWS[$route];
                    $choices[$name] = $route;
                }
            }

            /** @var User $user */
            $user = $options['user'];

            foreach ($this->reportingService->getAvailableReports($user) as $report) {
                $label = $this->translator->trans('menu.reporting') . ': ' . $this->translator->trans($report->getLabel(), [], 'reporting');
                $choices[$label] = $report->getRoute();
            }

            return $choices;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
