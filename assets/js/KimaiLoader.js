/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiLoader: bootstrap the application and all plugins
 */

import { Settings } from 'luxon';
import KimaiTranslation from "./KimaiTranslation";
import KimaiConfiguration from "./KimaiConfiguration";
import KimaiContainer from "./KimaiContainer";
import KimaiActiveRecordsDuration from './plugins/KimaiActiveRecordsDuration.js';
import KimaiDatatableColumnView from './plugins/KimaiDatatableColumnView.js';
import KimaiThemeInitializer from "./plugins/KimaiThemeInitializer";
import KimaiDateRangePicker from "./plugins/KimaiDateRangePicker";
import KimaiDatatable from "./plugins/KimaiDatatable";
import KimaiToolbar from "./plugins/KimaiToolbar";
import KimaiAPI from "./plugins/KimaiAPI";
import KimaiAlternativeLinks from "./plugins/KimaiAlternativeLinks";
import KimaiAjaxModalForm from "./plugins/KimaiAjaxModalForm";
import KimaiActiveRecords from "./plugins/KimaiActiveRecords";
import KimaiRecentActivities from "./plugins/KimaiRecentActivities";
import KimaiEvent from "./plugins/KimaiEvent";
import KimaiAPILink from "./plugins/KimaiAPILink";
import KimaiAlert from "./plugins/KimaiAlert";
import KimaiAutocomplete from "./plugins/KimaiAutocomplete";
import KimaiFormSelect from "./plugins/KimaiFormSelect";
import KimaiForm from "./plugins/KimaiForm";
import KimaiDatePicker from "./plugins/KimaiDatePicker";
import KimaiConfirmationLink from "./plugins/KimaiConfirmationLink";
import KimaiMultiUpdateTable from "./plugins/KimaiMultiUpdateTable";
import KimaiDateUtils from "./plugins/KimaiDateUtils";
import KimaiEscape from "./plugins/KimaiEscape";
import KimaiEditTimesheetForm from "./plugins/KimaiEditTimesheetForm";

export default class KimaiLoader {

    constructor(configurations, translations) {
        // set the current locale for all javascript components
        Settings.defaultLocale = configurations['locale'].replace('_', '-').toLowerCase();

        const kimai = new KimaiContainer(
            new KimaiConfiguration(configurations),
            new KimaiTranslation(translations)
        );

        kimai.registerPlugin(new KimaiEscape());
        kimai.registerPlugin(new KimaiEvent());
        kimai.registerPlugin(new KimaiAPI());
        kimai.registerPlugin(new KimaiAlert());
        kimai.registerPlugin(new KimaiDateUtils());
        kimai.registerPlugin(new KimaiFormSelect('.selectpicker', 'select[data-related-select]'));
        kimai.registerPlugin(new KimaiConfirmationLink('confirmation-link'));
        kimai.registerPlugin(new KimaiActiveRecordsDuration());
        kimai.registerPlugin(new KimaiDatatableColumnView('data-column-visibility'));
        kimai.registerPlugin(new KimaiDateRangePicker('input[data-daterangepicker="on"]'));
        kimai.registerPlugin(new KimaiDatePicker('input[data-datepicker="on"]'));
        kimai.registerPlugin(new KimaiDatatable('section.content', 'table.dataTable'));
        kimai.registerPlugin(new KimaiToolbar('form.searchform', 'toolbar-action'));
        kimai.registerPlugin(new KimaiAlternativeLinks('.alternative-link'));
        kimai.registerPlugin(new KimaiAjaxModalForm('.modal-ajax-form'));
        kimai.registerPlugin(new KimaiRecentActivities('.notifications-menu'));
        kimai.registerPlugin(new KimaiActiveRecords('.messages-menu', '.messages-menu-empty'));
        kimai.registerPlugin(new KimaiAPILink('api-link'));
        kimai.registerPlugin(new KimaiAutocomplete('.js-autocomplete'));
        kimai.registerPlugin(new KimaiForm());
        kimai.registerPlugin(new KimaiThemeInitializer());
        kimai.registerPlugin(new KimaiMultiUpdateTable());
        kimai.registerPlugin(new KimaiEditTimesheetForm());

        // notify all listeners that Kimai plugins can now be registered
        /** @type {KimaiEvent} EVENT */
        const EVENT = kimai.getPlugin('event');
        EVENT.trigger('kimai.pluginRegister', {'kimai': kimai});

        // initialize all plugins
        kimai.getPlugins().map(plugin => { plugin.init(); });

        // notify all listeners that Kimai is now ready to be used
        EVENT.trigger('kimai.initialized', {'kimai': kimai});

        this.kimai = kimai;
    }

    getKimai() {
        return this.kimai;
    }

}
