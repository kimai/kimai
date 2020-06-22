/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiDateUtils: responsible for handling date specific tasks
 */

import KimaiPlugin from '../KimaiPlugin';
import moment from 'moment';

export default class KimaiDateUtils extends KimaiPlugin {

    getId() {
        return 'date';
    }

    getWeekDaysShort() {
        return moment.localeData().weekdaysShort();
    }

    getMonthNames() {
        return moment.localeData().months();
    }

}
