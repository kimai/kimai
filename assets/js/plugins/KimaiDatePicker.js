/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDatePicker: single date selects (currently unused)
 */

import KimaiPlugin from '../KimaiPlugin';
import Litepicker from 'litepicker';
import 'litepicker/dist/plugins/mobilefriendly';

export default class KimaiDatePicker extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'date-picker';
    }

    init() {
        window.disableLitepickerStyles = true;
        this.pickers = {};
    }

    activate(selector) {
        const TRANSLATE = this.getTranslation();
        const FIRST_DOW = this.getConfigurations().getFirstDayOfWeek(false);
        const LANGUAGE = this.getConfigurations().getLanguage();

        let options = {};

        let previous = `<i class="fas fa-chevron-left"></i>`;
        let next = `<i class="fas fa-chevron-right"></i>`;

        if (this.getConfigurations().isRTL()) {
            previous = `<i class="fas fa-chevron-right"></i>`;
            next = `<i class="fas fa-chevron-left"></i>`;
        }

        options = {...options, ...{
            buttonText: {
                previousMonth: previous,
                nextMonth: next,
                apply: TRANSLATE.get('confirm'),
                cancel: TRANSLATE.get('cancel'),
            },
        }};

        this.pickers[selector] = [].slice.call(document.querySelectorAll(selector + ' ' + this.selector)).map((element) => {
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
            }};

            return new Litepicker(this._prepareOptions(options));
        });
    }

    _prepareOptions(options) {
        return {...options, ...{
            plugins: ['mobilefriendly'],
        }};
    }

    destroy(selector) {
        if (this.pickers[selector] === undefined) {
            return;
        }

        for (const picker of this.pickers[selector]) {
            picker.destroy();
        }

        delete this.pickers[selector];
    }

}
