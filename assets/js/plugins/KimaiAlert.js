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
            type: 'error',
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
            //toast: true,
            //timer: 3000,
            timer: 1500,
            position: 'top-end',
            showConfirmButton: false,
            type: 'success',
            title: message,
        });
    }

}
