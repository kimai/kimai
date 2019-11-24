/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiSelectDataAPI: <select> boxes with dynamic data from API
 */

import jQuery from 'jquery';
import KimaiPlugin from "../KimaiPlugin";

export default class KimaiSelectDataAPI extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'select-data-api';
    }

    init() {
        this.activateApiSelects(this.selector);
    }

    activateApiSelects(selector) {
        const self = this;
        const API = this.getContainer().getPlugin('api');

        jQuery('body').on('change', selector, function(event) {
            let apiUrl = jQuery(this).attr('data-api-url').replace('-s-', jQuery(this).val());
            const targetSelect = '#' + jQuery(this).attr('data-related-select');

            // if the related target select does not exist, we do not need to load the related data
            if (jQuery(targetSelect).length === 0) {
                return;
            }

            if (jQuery(this).val() === '') {
                if (jQuery(this).attr('data-empty-url') === undefined) {
                    self._updateSelect(targetSelect, {});
                    jQuery(targetSelect).attr('disabled', 'disabled');
                    return;
                }
                apiUrl = jQuery(this).attr('data-empty-url').replace('-s-', jQuery(this).val());
            }

            jQuery(targetSelect).removeAttr('disabled');

            API.get(apiUrl, {}, function(data){
                self._updateSelect(targetSelect, data);
            });
        });
    }

    _updateSelect(selectName, data) {
        const options = {};
        for (const apiData of data) {
            let title = '__empty__';
            if (apiData.hasOwnProperty('parentTitle') && apiData.parentTitle !== null) {
                title = apiData.parentTitle;
            }
            if (!options.hasOwnProperty(title)) {
                options[title] = [];
            }
            options[title].push(apiData);
        }

        const ordered = {};
        Object.keys(options).sort().forEach(function(key) {
            ordered[key] = options[key];
        });

        this.getContainer().getPlugin('form-select').updateOptions(selectName, ordered);
    }

}
