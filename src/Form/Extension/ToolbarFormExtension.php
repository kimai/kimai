<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Form\Helper\ToolbarHelper;
use App\Form\Toolbar\ExportToolbarForm;
use App\Form\Toolbar\InvoiceToolbarForm;
use App\Form\Toolbar\TimesheetToolbarForm;
use App\Form\Toolbar\UserToolbarForm;
use App\Reporting\MonthlyUserList\MonthlyUserListForm;
use App\Reporting\WeeklyUserList\WeeklyUserListForm;
use App\Reporting\YearlyUserList\YearlyUserListForm;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class ToolbarFormExtension extends AbstractTypeExtension
{
    public function __construct(private ToolbarHelper $toolbarHelper)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            InvoiceToolbarForm::class,
            ExportToolbarForm::class,
            TimesheetToolbarForm::class,
            UserToolbarForm::class,
            WeeklyUserListForm::class,
            MonthlyUserListForm::class,
            YearlyUserListForm::class,
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->toolbarHelper->cleanupForm($builder);
    }
}
