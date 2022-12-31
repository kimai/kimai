/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDateRangePicker: activate the (daterange picker) compound field in toolbar
 */

import KimaiDatePicker from "./KimaiDatePicker";

export default class KimaiDateRangePicker extends KimaiDatePicker {

    prepareOptions(options)
    {
        return {...options, ...{
            plugins: ['mobilefriendly'],
            singleMode: false,
            autoRefresh: true,
        }};
    }

}
