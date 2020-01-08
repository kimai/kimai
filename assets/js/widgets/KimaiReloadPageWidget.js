/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiReloadPageWidget: a simple helper to reload the page on events
 */

import jQuery from "jquery";

export default class KimaiReloadPageWidget {

    constructor(events) {
        this.overlay = jQuery('<div class="overlay-wrapper"><div class="overlay"><div class="fa fa-refresh fa-spin"></div></div></div>');
        this.widget = jQuery('div.content-wrapper');

        const self = this;

        const reloadPage = function (event) {
            self.loadPage(document.location);
        };

        for (const eventName of events.split(' ')) {
            document.addEventListener(eventName, reloadPage);
        }
    }
    
    static create(events) {
        return new KimaiReloadPageWidget(events);
    }
    
    _showOverlay() {
        this.widget.append(this.overlay);
    }
    
    _hideOverlay() {
        jQuery(this.overlay).remove();
    }
    
    loadPage(url) {
        const self = this;
        
        self._showOverlay();

        jQuery.ajax({
            url: url,
            data: {},
            success: function (response) {
                jQuery('section.content').replaceWith(
                    jQuery(response).find('section.content')
                );
                self._hideOverlay();
            },
            dataType: 'html',
            error: function(jqXHR, textStatus, errorThrown) {
                self._hideOverlay();
                document.location = url;
            }
        });        
    }

}
