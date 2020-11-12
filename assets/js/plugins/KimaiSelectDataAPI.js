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
import moment from 'moment';

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
            const targetSelect = '#' + this.dataset['relatedSelect'];

            // if the related target select does not exist, we do not need to load the related data
            if (jQuery(targetSelect).length === 0) {
                return;
            }

            let formPrefix = jQuery(this).parents('form').first().attr('name');
            if (formPrefix === undefined || formPrefix === null) {
                formPrefix = '';
            } else {
                formPrefix += '_';
            }
            
            let newApiUrl = self._buildUrlWithFormFields(this.dataset['apiUrl'], formPrefix);

            const selectValue = jQuery(this).val();

            // Problem: select a project with activities and then select a customer that has no project
            // results in a wrong URL, it triggers "activities?project=" instead of using the "emptyUrl"
            if (selectValue === undefined || selectValue === null || selectValue === '' || (Array.isArray(selectValue) && selectValue.length === 0)) {
                if (this.dataset['emptyUrl'] === undefined) {
                    self._updateSelect(targetSelect, {});
                    jQuery(targetSelect).attr('disabled', 'disabled');
                    return;
                }
                newApiUrl = self._buildUrlWithFormFields(this.dataset['emptyUrl'], formPrefix);
            }

            jQuery(targetSelect).removeAttr('disabled');

            API.get(newApiUrl, {}, function(data){
                self._updateSelect(targetSelect, data);
            });
        });
    }
    
    _buildUrlWithFormFields(apiUrl, formPrefix) {
        let newApiUrl = apiUrl;

        apiUrl.split('?')[1].split('&').forEach(item => {
            let [key, value] = item.split('=');
            let decoded = decodeURIComponent(value);
            let test = decoded.match(/%(.*)%/);
            if (test !== null) {
                let targetField = jQuery('#' + formPrefix + test[1]);
                let newValue = '';
                if (targetField.length === 0) {
                    // happens for example:
                    // - in duration only mode, when the end field is not found
                    // console.log('ERROR: Cannot find field with name "' + test[1] + '" by selector: #' + formPrefix + test[1]);
                } else {
                    if (targetField.val() !== null) {
                        newValue = targetField.val();

                        if (newValue !== '') {
                            // having that special case here is far from being perfect... but for now it works ;-)
                            if (targetField.data('daterangepicker') !== undefined) {
                                if (key === 'begin' || key === 'start' || targetField.data('daterangepicker').singleDatePicker) {
                                    newValue = targetField.data('daterangepicker').startDate.format(moment.HTML5_FMT.DATETIME_LOCAL_SECONDS);
                                } else if (key === 'end') {
                                    newValue = targetField.data('daterangepicker').endDate.format(moment.HTML5_FMT.DATETIME_LOCAL_SECONDS);
                                }
                            } else if (targetField.data('format') !== undefined) {
                                if (moment(newValue, targetField.data('format')).isValid()) {
                                    newValue = moment(newValue, targetField.data('format')).format(moment.HTML5_FMT.DATETIME_LOCAL_SECONDS);
                                }
                            }
                        } else {
                            // happens for example:
                            // - when the end date is not set on a timesheet record and the project list is loaded (as the URL contains the %end% replacer)
                            // console.log('Empty value found for field with name "' + test[1] + '" by selector: #' + formPrefix + test[1]);
                        }
                    } else {
                        // happens for example:
                        // - when a customer without projects is selected
                        // console.log('ERROR: Empty field with name "' + test[1] + '" by selector: #' + formPrefix + test[1]);
                    }
                }

                if (Array.isArray(newValue)) {
                    newValue = newValue.join(',');
                }

                newApiUrl = newApiUrl.replace(value, newValue);
            }
        });

        return newApiUrl;
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
