<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ThemeJavascriptTranslationsEvent extends Event
{
    /**
     * Contains default translations for all views.
     *
     * For usage see "templates/base.html.twig" in block "javascripts".
     *
     * Structure
     * ---------
     * Javascript key: [Translation key, Translation domain]
     *
     * @var array<string, array<int, string>>
     */
    private $translations = [
        'confirm' => ['confirm', 'messages'],
        'cancel' => ['cancel', 'messages'],
        'today' => ['daterangepicker.today', 'daterangepicker'],
        'yesterday' => ['daterangepicker.yesterday', 'daterangepicker'],
        'lastWeek' => ['daterangepicker.lastWeek', 'daterangepicker'],
        'thisWeek' => ['daterangepicker.thisWeek', 'daterangepicker'],
        'lastMonth' => ['daterangepicker.lastMonth', 'daterangepicker'],
        'thisMonth' => ['daterangepicker.thisMonth', 'daterangepicker'],
        'lastYear' => ['daterangepicker.lastYear', 'daterangepicker'],
        'thisYear' => ['daterangepicker.thisYear', 'daterangepicker'],
        'customRange' => ['daterangepicker.customRange', 'daterangepicker'],
        'timesheet.start.success' => ['timesheet.start.success', 'flashmessages'],
        'timesheet.start.error' => ['timesheet.start.error', 'flashmessages'],
        'timesheet.start.exceeded_limit' => ['timesheet.start.exceeded_limit', 'flashmessages'],
        'timesheet.stop.success' => ['timesheet.stop.success', 'flashmessages'],
        'timesheet.stop.error' => ['timesheet.stop.error', 'flashmessages'],
        'action.update.success' => ['action.update.success', 'flashmessages'],
        'action.update.error' => ['action.update.error', 'flashmessages'],
        'action.delete.success' => ['action.delete.success', 'flashmessages'],
        'action.delete.error' => ['action.delete.error', 'flashmessages'],
        'confirm.delete' => ['confirm.delete', 'messages'],
        'delete' => ['action.delete', 'messages'],
        'login.required' => ['login_required', 'messages'],
    ];

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function setTranslation(string $key, string $translationKey, string $translationDomain = 'messages'): ThemeJavascriptTranslationsEvent
    {
        $this->translations[$key] = [$translationKey, $translationDomain];

        return $this;
    }
}
