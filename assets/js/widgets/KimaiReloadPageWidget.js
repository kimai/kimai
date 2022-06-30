/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiReloadPageWidget: a simple helper to reload the page on events
 */

export default class KimaiReloadPageWidget {

    constructor(events, fullReload) {
        const self = this;

        const reloadPage = function (event) {
            if (fullReload) {
                document.location.reload();
            } else {
                self.loadPage(document.location);
            }
        };

        for (const eventName of events.split(' ')) {
            document.addEventListener(eventName, reloadPage);
        }
    }
    
    static create(events, fullReload) {
        if (fullReload === undefined || fullReload === null) {
            fullReload = false;
        }
        return new KimaiReloadPageWidget(events, fullReload);
    }
    
    _showOverlay() {
        document.dispatchEvent(new CustomEvent('kimai.reloadContent', {detail: 'div.page-wrapper'}));
    }

    _hideOverlay() {
        document.dispatchEvent(new Event('kimai.reloadedContent'));
    }
    
    loadPage(url) {
        const self = this;
        
        self._showOverlay();

        window.kimai.getPlugin('fetch').fetch(url)
            .then(response => {
                response.text().then((text) => {
                    const temp = document.createElement('div');
                    temp.innerHTML = text;
                    const newContent = temp.querySelector('section.content');
                    document.querySelector('section.content').replaceWith(newContent);
                    document.dispatchEvent(new Event('kimai.reloadPage'));
                    self._hideOverlay();
                });
            })
            .catch(error => {
                self._hideOverlay();
                document.location = url;
            });
    }

}
