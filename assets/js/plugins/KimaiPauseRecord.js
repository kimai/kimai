/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiPauseRecord
 *
 * allows to pause records
 * THIS IS JUST A DRAFT FOR THE DOM, IT IS NOT SUPPORTED IN KIMAI ITSELF!
 */

import jQuery from 'jquery';
import KimaiPlugin from '../KimaiPlugin';

export default class KimaiPauseRecord extends KimaiPlugin {

    constructor(selector) {
        super();
        this.selector = selector;
    }

    getId() {
        return 'pause-record';
    }

    init() {
        this.activate(this.selector);
    }

    activate(selector) {
        jQuery(selector + ' .pull-left i').hover(function () {
            let link = jQuery(this).parents('a');
            link.attr('href', link.attr('href').replace('/stop', '/pause'));
            jQuery(this).removeClass('fa-stop-circle').addClass('fa-pause-circle').addClass('text-orange');
        },function () {
            let link = jQuery(this).parents('a');
            link.attr('href', link.attr('href').replace('/pause', '/stop'));
            jQuery(this).removeClass('fa-pause-circle').removeClass('text-orange').addClass('fa-stop-circle');
        });
    }

}
