/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAlert: notifications for Kimai
 */

import KimaiPlugin from "../KimaiPlugin";

/**
 * In the future the "lang" and "dir" options could be set via constructor.
 * @see https://developer.mozilla.org/de/docs/Web/API/notification
 */
export default class KimaiNotification extends KimaiPlugin {

    getId() {
        return 'notification';
    }

    _checkNotificationPromiseSupport() {
        try {
            Notification.requestPermission().then();
        } catch(e) {
            return false;
        }

        return true;
    }

    hasNotificationSupport() {
        if (!window.Notification) {
            return false;
        }

        if (Notification.permission === 'denied') {
            return false;
        }

        if (Notification.permission === "granted") {
            return true;
        }

        let result = false;

        try {
            Notification.requestPermission().then((permission) => {
                if (permission === "granted") {
                    result = true;
                }
            });
        } catch (e) {
            Notification.requestPermission(function (permission) {
                if (permission === "granted") {
                    result = true;
                }
            });
        }

        return result;
    }

    notify(title, message, icon, options) {
        if (false === this.hasNotificationSupport()) {
            return;
        }

        let opts = { body: message };

        if (icon !== null) {
            opts.icon = icon;
        }

        if (options !== null) {
            opts = { ...opts, ...options};
        }

        return new Notification(title, opts);
    }
}
