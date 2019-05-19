/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiLoader: bootstrap the application and all plugins
 */

import moment from 'moment';
import KimaiTranslation from "./KimaiTranslation";
import KimaiConfiguration from "./KimaiConfiguration";
import KimaiContainer from "./KimaiContainer";
import KimaiActiveRecordsDuration from './plugins/KimaiActiveRecordsDuration.js';
import KimaiDatatableColumnView from './plugins/KimaiDatatableColumnView.js';
import KimaiThemeInitializer from "./plugins/KimaiThemeInitializer";
import KimaiJqueryPluginInitializer from "./plugins/KimaiJqueryPluginInitializer";
import KimaiDateRangePicker from "./plugins/KimaiDateRangePicker";
import KimaiDatatable from "./plugins/KimaiDatatable";
import KimaiToolbar from "./plugins/KimaiToolbar";
import KimaiAPI from "./plugins/KimaiAPI";
import KimaiSelectDataAPI from "./plugins/KimaiSelectDataAPI";
import KimaiDateTimePicker from "./plugins/KimaiDateTimePicker";
import KimaiAlternativeLinks from "./plugins/KimaiAlternativeLinks";
import KimaiAjaxModalForm from "./plugins/KimaiAjaxModalForm";
import KimaiActiveRecords from "./plugins/KimaiActiveRecords";
import KimaiRecentActivities from "./plugins/KimaiRecentActivities";
import KimaiEvent from "./plugins/KimaiEvent";
import KimaiAPILink from "./plugins/KimaiAPILink";
import KimaiAlert from "./plugins/KimaiAlert";
import KimaiAutocomplete from "./plugins/KimaiAutocomplete";
import KimaiToolbarAction from "./plugins/KimaiToolbarAction";

export default class KimaiLoader {

    constructor(configurations, translations) {
        // set the current locale for all javascript components
        moment.locale(configurations['locale']);

        const kimai = new KimaiContainer(
            new KimaiConfiguration(configurations),
            new KimaiTranslation(translations)
        );

        kimai.registerPlugin(new KimaiEvent());
        kimai.registerPlugin(new KimaiAPI());
        kimai.registerPlugin(new KimaiAlert());
        kimai.registerPlugin(new KimaiActiveRecordsDuration('[data-since]'));
        kimai.registerPlugin(new KimaiDatatableColumnView('data-column-visibility'));
        kimai.registerPlugin(new KimaiThemeInitializer());
        kimai.registerPlugin(new KimaiJqueryPluginInitializer());
        kimai.registerPlugin(new KimaiDateRangePicker('.content-wrapper'));
        kimai.registerPlugin(new KimaiDateTimePicker('.content-wrapper'));
        kimai.registerPlugin(new KimaiDatatable('table.dataTable'));
        kimai.registerPlugin(new KimaiToolbar());
        kimai.registerPlugin(new KimaiSelectDataAPI('select[data-related-select]'));
        kimai.registerPlugin(new KimaiAlternativeLinks('.alternative-link'));
        kimai.registerPlugin(new KimaiAjaxModalForm('.modal-ajax-form'));
        kimai.registerPlugin(new KimaiRecentActivities('li.notifications-menu'));
        kimai.registerPlugin(new KimaiActiveRecords('li.messages-menu', 'li.messages-menu-empty'));
        kimai.registerPlugin(new KimaiAPILink('api-link'));
        kimai.registerPlugin(new KimaiAutocomplete('.js-autocomplete'));
        kimai.registerPlugin(new KimaiToolbarAction('toolbar-action'));
        //kimai.registerPlugin(new KimaiPauseRecord('li.messages-menu ul.menu li'));

        // notify all listeners that Kimai plugins can now be registered
        kimai.getPlugin('event').trigger('kimai.pluginRegister', {'kimai': kimai});

        // initialize all plugins
        kimai.getPlugins().map(plugin => { plugin.init(); });

        // notify all listeners that Kimai is now ready to be used
        kimai.getPlugin('event').trigger('kimai.initialized', {'kimai': kimai});

        this.kimai = kimai;
    }

    getKimai() {
        return this.kimai;
    }

}
