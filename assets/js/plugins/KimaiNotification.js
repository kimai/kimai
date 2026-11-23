/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] Notification: notifications for Kimai
 */

import KimaiPlugin from '../KimaiPlugin';

export default class KimaiNotification extends KimaiPlugin {

    getId()
    {
        return 'notification';
    }

    isSupported()
    {
        if (!window.Notification) {
            return false;
        }

        if (Notification.permission === 'denied') {
            return false;
        }

        return Notification.permission === "granted";
    }

    request(callback)
    {
        try {
            Notification.requestPermission().then((permission) => {
                if (permission === "granted") {
                    callback(true);
                } else if (permission === "default") {
                    callback(null);
                } else {
                    callback(false);
                }
            });
        } catch (e) { // eslint-disable-line no-unused-vars
            Notification.requestPermission((permission) => {
                if (permission === "granted") {
                    callback(true);
                } else if (permission === "default") {
                    callback(null);
                } else {
                    callback(false);
                }
            });
        }
    }

    notify(title, message, icon, options)
    {
        this.request((permission) => {

            if (permission !== true) {
                /** @type KimaiAlert */
                const ALERT = this.getPlugin('alert');
                ALERT.info(message);
            }

            let opts = {
                body: message,
                dir: this.getConfigurations().isRTL() ? 'rtl' : 'ltr',
            };
            //opts.requireInteraction = true;
            //opts.renotify = true;
            /*
            if (options.tag === undefined) {
                opts.tag = 'kimai';
            }
            */
            if (icon !== undefined && icon !== null) {
                opts.icon = icon;
            }

            let nTitle = 'Kimai';
            if (title !== null) {
                nTitle = nTitle + ': ' + title;
            }

            if (options !== undefined && options !== null) {
                opts = { ...opts, ...options};
            }

            const notification = new window.Notification(nTitle, opts);

            notification.onclick = function () {
                window.focus();
                notification.close();
            };
        });
    }
}
