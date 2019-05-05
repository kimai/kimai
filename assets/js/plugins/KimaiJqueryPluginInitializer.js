/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiJqueryPluginInitializer: initialize jQuery plugins
 */

import jQuery from 'jquery';
import KimaiPlugin from '../KimaiPlugin';

export default class KimaiJqueryPluginInitializer extends KimaiPlugin {

    getId() {
        return 'jquery-plugin-initializer';
    }

    init() {
        // activate the dropdown functionality
        jQuery('.dropdown-toggle').dropdown();
        // activate the tooltip functionality
        jQuery('[data-toggle="tooltip"]').tooltip();
    }

}
