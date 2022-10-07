<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Form\Toolbar\ExportToolbarForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Form\Toolbar\InvoiceToolbarSimpleForm;
use App\Form\Toolbar\TimesheetExportToolbarForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Form\Toolbar\UserToolbarForm;
use App\User\TeamService;
use App\User\UserService;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class ToolbarFormExtension extends AbstractTypeExtension
{
    private $userService;
    private $teamService;
    private $teamNames = ['team', 'teams', 'searchTeams'];
    private $userNames = ['user', 'users'];

    public function __construct(UserService $userService, TeamService $teamService)
    {
        $this->userService = $userService;
        $this->teamService = $teamService;
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            InvoiceToolbarForm::class,
            InvoiceToolbarSimpleForm::class,
            ExportToolbarForm::class,
            TimesheetToolbarForm::class,
            TimesheetExportToolbarForm::class,
            UserToolbarForm::class,
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $deleteUser = false;
        foreach ($this->userNames as $name) {
            if ($builder->has($name) && $this->userService->countUser(true) < 2) {
                $deleteUser = true;
                break;
            }
        }

        if ($deleteUser) {
            foreach ($this->userNames as $name) {
                if ($builder->has($name)) {
                    $builder->remove($name);
                }
            }
        }

        $deleteTeams = false;
        foreach ($this->teamNames as $name) {
            if ($builder->has($name) && !$this->teamService->hasTeams()) {
                $deleteTeams = true;
                break;
            }
        }

        if ($deleteTeams) {
            foreach ($this->teamNames as $name) {
                if ($builder->has($name)) {
                    $builder->remove($name);
                }
            }
        }
    }
}
