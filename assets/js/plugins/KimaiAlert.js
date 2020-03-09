/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*!
 * [KIMAI] KimaiAlert: notifications for Kimai
 */

import Swal from 'sweetalert2'
import KimaiPlugin from "../KimaiPlugin";

export default class KimaiAlert extends KimaiPlugin {

    getId() {
        return 'alert';
    }

    error(title, message) {
        const translation = this.getContainer().getTranslation();
        if (translation.has(title)) {
            title = translation.get(title);
        }
        if (translation.has(message)) {
            message = translation.get(message);
        }
        Swal.fire({
            icon: 'error',
            title: title.replace('%reason%', ''),
            text: message,
        });
    }

    success(message) {
        const translation = this.getContainer().getTranslation();

        if (translation.has(message)) {
            message = translation.get(message);
        }

        Swal.fire({
            timer: 2000,
            timerProgressBar: true,
            toast: true,
            position: 'top',
            showConfirmButton: false,
            icon: 'success',
            title: message,
        });
    }

    /**
     * Callback receives a value and needs to decide what should happen with it
     *
     * @param message
     * @param callback
     */
    question(message, callback) {
        const translation = this.getContainer().getTranslation();

        if (translation.has(message)) {
            message = translation.get(message);
        }

        Swal.fire({
            title: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: translation.get('confirm'),
            cancelButtonText: translation.get('cancel')
        }).then((result) => {
            callback(result.value);
        });
    }

}
