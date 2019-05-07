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

export default class KimaiLoader {

    constructor(configurations, translations) {
        const defaultTranslations = {
            today: 'Today',
            yesterday: 'Yesterday',
            apply: 'Apply',
            cancel: 'Cancel',
            thisWeek: 'This week',
            lastWeek: 'Last week',
            thisMonth: 'This month',
            lastMonth: 'Last month',
            thisYear: 'This year',
            lastYear: 'Last year',
            customRange: 'Custom range',
        };

        translations = Object.assign(defaultTranslations, translations);

        const defaultConfigurations = {
            locale: 'en',
            twentyFourHours: true
        };

        configurations = Object.assign(defaultConfigurations, configurations);

        // set the current locale for all javascript components
        moment.locale(configurations['locale']);

        const kimai = new KimaiContainer(
            new KimaiConfiguration(configurations),
            new KimaiTranslation(translations)
        );

        kimai.registerPlugin(new KimaiAPI());
        kimai.registerPlugin(new KimaiActiveRecordsDuration('[data-since]'));
        kimai.registerPlugin(new KimaiDatatableColumnView('data-column-visibility'));
        kimai.registerPlugin(new KimaiThemeInitializer());
        kimai.registerPlugin(new KimaiJqueryPluginInitializer());
        kimai.registerPlugin(new KimaiDateRangePicker('.content-wrapper'));
        kimai.registerPlugin(new KimaiDateTimePicker('.content-wrapper'));
        kimai.registerPlugin(new KimaiDatatable());
        kimai.registerPlugin(new KimaiToolbar());
        kimai.registerPlugin(new KimaiSelectDataAPI('select[data-related-select]'));
        kimai.registerPlugin(new KimaiAlternativeLinks('.alternative-link'));
        kimai.registerPlugin(new KimaiAjaxModalForm('.modal-ajax-form'));
        //kimai.registerPlugin(new KimaiPauseRecord('li.messages-menu ul.menu li'));
        kimai.registerPlugin(new KimaiActiveRecords('.messages-menu'));

        // notify all listeners that Kimai plugins can now be registered
        this._sendEvent('kimai.pluginRegister');

        // initialize all plugins
        kimai.getPlugins().map(plugin => { plugin.init(); });

        // notify all listeners that Kimai is now ready to be used
        this._sendEvent('kimai.initialized');
    }

    _sendEvent(name) {
        document.dispatchEvent(new Event(name));
    }

}
