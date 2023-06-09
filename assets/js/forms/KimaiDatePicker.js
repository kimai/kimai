/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatePicker: single date selects (currently unused)
 */

import { Litepicker } from 'litepicker';
import 'litepicker/dist/plugins/mobilefriendly';
import KimaiFormPlugin from "./KimaiFormPlugin";

export default class KimaiDatePicker extends KimaiFormPlugin {

    constructor(selector)
    {
        super();
        this._selector = selector;
    }

    init()
    {
        window.disableLitepickerStyles = true;
        this._pickers = [];
    }

    /**
     * @param {HTMLFormElement} form
     * @return boolean
     */
    supportsForm(form) // eslint-disable-line no-unused-vars
    {
        return true;
    }

    /**
     * @param {HTMLFormElement} form
     */
    activateForm(form)
    {
        const FIRST_DOW = this.getConfigurations().getFirstDayOfWeek(false);
        const LANGUAGE = this.getConfigurations().getLanguage();

        let options = {
            buttonText: {
                previousMonth: `<i class="fas fa-chevron-left"></i>`,
                nextMonth: `<i class="fas fa-chevron-right"></i>`,
                apply: this.translate('confirm'),
                cancel: this.translate('cancel'),
            },
        };

        const newPickers = [].slice.call(form.querySelectorAll(this._selector)).map((element) => {
            if (element.dataset.format === undefined) {
                console.log('Trying to bind litepicker to an element without data-format attribute');
            }
            options = {...options, ...{
                format: element.dataset.format,
                showTooltip: false,
                element: element,
                lang: LANGUAGE,
                autoRefresh: true,
                firstDay: FIRST_DOW, // Litepicker: 0 = Sunday, 1 = Monday
                setup: (picker) => {
                    // nasty hack, because litepicker does not trigger change event on the input and the available
                    // event "selected" is triggered why to often, even when moving the cursor inside the input
                    // element (not even typing is necessary) and so we have to make sure that the manual "click" event
                    // (works for touch as well) happened before we actually dispatch the change event manually ...
                    // what? report forms would be submitted upon cursor move without the "preselect” check
                    picker.on('preselect', (date1, date2) => {  // eslint-disable-line no-unused-vars
                        picker._wasPreselected = true;
                    });
                    picker.on('selected', (date1, date2) => {  // eslint-disable-line no-unused-vars
                        if (picker._wasPreselected !== undefined) {
                            element.dispatchEvent(new Event('change', {bubbles: true}));
                            delete picker._wasPreselected;
                        }
                    });

                    // only if mobile.friendly plugin is activated
                    if (picker.backdrop !== undefined) {
                        // the node needs to be moved, so the flat form layout works properly (e.g. for date types)
                        document.body.appendChild(picker.backdrop);
                    }
                },
            }};

            return [element, new Litepicker(this.prepareOptions(options))];
        });

        this._pickers = this._pickers.concat(newPickers);
    }

    prepareOptions(options)
    {
        return {...options, ...{
            plugins: ['mobilefriendly'],
        }};
    }

    /**
     * @param {HTMLFormElement} form
     */
    destroyForm(form)
    {
        [].slice.call(form.querySelectorAll(this._selector)).map((element) => {
            for (let i = 0; i < this._pickers.length; i++) {
                if (this._pickers[i][0] === element) {
                    this._pickers[i][1].destroy();
                    this._pickers.splice(i, 1);
                }
            }
        });
    }

}
