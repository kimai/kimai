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
import KimaiDatatableColumnView from './plugins/KimaiDatatableColumnView.js';
import KimaiThemeInitializer from "./plugins/KimaiThemeInitializer";
import KimaiDateRangePicker from "./forms/KimaiDateRangePicker";
import KimaiDatatable from "./plugins/KimaiDatatable";
import KimaiToolbar from "./plugins/KimaiToolbar";
import KimaiAPI from "./plugins/KimaiAPI";
import KimaiAlternativeLinks from "./plugins/KimaiAlternativeLinks";
import KimaiAjaxModalForm from "./plugins/KimaiAjaxModalForm";
import KimaiActiveRecords from "./plugins/KimaiActiveRecords";
import KimaiEvent from "./plugins/KimaiEvent";
import KimaiAPILink from "./plugins/KimaiAPILink";
import KimaiAlert from "./plugins/KimaiAlert";
import KimaiAutocomplete from "./forms/KimaiAutocomplete";
import KimaiFormSelect from "./forms/KimaiFormSelect";
import KimaiForm from "./plugins/KimaiForm";
import KimaiDatePicker from "./forms/KimaiDatePicker";
import KimaiConfirmationLink from "./plugins/KimaiConfirmationLink";
import KimaiMultiUpdateTable from "./plugins/KimaiMultiUpdateTable";
import KimaiDateUtils from "./plugins/KimaiDateUtils";
import KimaiEscape from "./plugins/KimaiEscape";
import KimaiFetch from "./plugins/KimaiFetch";
import KimaiTimesheetForm from "./forms/KimaiTimesheetForm";
import KimaiTeamForm from "./forms/KimaiTeamForm";
import KimaiCopyDataForm from "./forms/KimaiCopyDataForm";
import KimaiDateNowForm from "./forms/KimaiDateNowForm";
import KimaiNotification from "./plugins/KimaiNotification";
import KimaiHotkeys from "./plugins/KimaiHotkeys";
import KimaiRemoteModal from "./plugins/KimaiRemoteModal";
import KimaiUser from "./plugins/KimaiUser";
import KimaiAutocompleteTags from "./forms/KimaiAutocompleteTags";

export default class KimaiLoader {

    constructor(configurations, translations) {
        // set the current locale for all javascript components
        Settings.defaultLocale = configurations['locale'].replace('_', '-').toLowerCase();
        Settings.defaultZone = configurations['timezone'];

        const kimai = new KimaiContainer(
            new KimaiConfiguration(configurations),
            new KimaiTranslation(translations)
        );

        // GLOBAL HELPER PLUGINS
        kimai.registerPlugin(new KimaiUser());
        kimai.registerPlugin(new KimaiEscape());
        kimai.registerPlugin(new KimaiEvent());
        kimai.registerPlugin(new KimaiAPI());
        kimai.registerPlugin(new KimaiAlert());
        kimai.registerPlugin(new KimaiFetch());
        kimai.registerPlugin(new KimaiDateUtils());
        kimai.registerPlugin(new KimaiNotification());

        // FORM PLUGINS
        kimai.registerPlugin(new KimaiFormSelect('.selectpicker', 'select[data-related-select]'));
        kimai.registerPlugin(new KimaiDateRangePicker('input[data-daterangepicker="on"]'));
        kimai.registerPlugin(new KimaiDatePicker('input[data-datepicker="on"]'));
        kimai.registerPlugin(new KimaiAutocomplete());
        kimai.registerPlugin(new KimaiAutocompleteTags());
        kimai.registerPlugin(new KimaiTimesheetForm());
        kimai.registerPlugin(new KimaiTeamForm());
        kimai.registerPlugin(new KimaiCopyDataForm());
        kimai.registerPlugin(new KimaiDateNowForm());
        kimai.registerPlugin(new KimaiForm());
        kimai.registerPlugin(new KimaiHotkeys());

        // SPECIAL FEATURES
        kimai.registerPlugin(new KimaiConfirmationLink('confirmation-link'));
        kimai.registerPlugin(new KimaiDatatableColumnView('data-column-visibility'));
        kimai.registerPlugin(new KimaiDatatable('section.content', 'table.dataTable'));
        kimai.registerPlugin(new KimaiToolbar('form.searchform', 'toolbar-action'));
        kimai.registerPlugin(new KimaiAlternativeLinks('.alternative-link'));
        kimai.registerPlugin(new KimaiAjaxModalForm('.modal-ajax-form', ['td.multiCheckbox', 'td.actions']));
        kimai.registerPlugin(new KimaiRemoteModal());
        kimai.registerPlugin(new KimaiActiveRecords());
        kimai.registerPlugin(new KimaiAPILink('api-link'));
        kimai.registerPlugin(new KimaiMultiUpdateTable());
        kimai.registerPlugin(new KimaiThemeInitializer());

        // notify all listeners that Kimai plugins can now be registered
        document.dispatchEvent(new CustomEvent('kimai.pluginRegister', {detail: {'kimai': kimai}}));

        // initialize all plugins
        kimai.getPlugins().map(plugin => { plugin.init(); });

        // notify all listeners that Kimai is now ready to be used
        document.dispatchEvent(new CustomEvent('kimai.initialized', {detail: {'kimai': kimai}}));

        this.kimai = kimai;
    }

    getKimai() {
        return this.kimai;
    }

}
